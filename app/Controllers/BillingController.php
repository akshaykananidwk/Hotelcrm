<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Audit;
use App\Core\App;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Guest;
use App\Models\Reservation;
use App\Services\PdfService;
use App\Services\Notification\NotificationManager;

class BillingController extends Controller
{
    private Invoice $invoices;

    public function __construct()
    {
        $this->invoices = new Invoice();
    }

    public function index(): void
    {
        $this->authorize('billing.view');
        $hotelId = $this->currentHotelId();
        $result = $this->invoices->withGuest($hotelId, $this->page());
        $this->view('billing/index', [
            'title' => 'Billing & Invoices',
            'invoices' => $result['data'],
            'pg' => $result,
        ]);
    }

    public function create(): void
    {
        $this->authorize('billing.manage');
        $hotelId = $this->currentHotelId();
        $this->view('billing/form', [
            'title' => 'New Invoice',
            'guests' => (new Guest())->all('hotel_id = ?', [$hotelId], 'first_name ASC'),
            'reservationId' => Request::get('reservation_id'),
        ]);
    }

    public function store(): void
    {
        $this->authorize('billing.manage');
        $hotelId = $this->currentHotelId();
        $guestId = (int) Request::input('guest_id') ?: null;
        $reservationId = (int) Request::input('reservation_id') ?: null;

        $db = App::db();
        $db->beginTransaction();
        try {
            $invoiceId = $this->invoices->create([
                'hotel_id' => $hotelId,
                'reservation_id' => $reservationId,
                'guest_id' => $guestId,
                'number' => $this->invoices->generateNumber(),
                'discount' => (float) Request::input('discount', 0),
                'gst_number' => Request::input('gst_number'),
                'place_of_supply' => Request::input('place_of_supply'),
                'notes' => Request::input('notes'),
                'created_by' => Auth::id(),
            ]);

            $descriptions = (array) Request::input('description', []);
            foreach ($descriptions as $i => $desc) {
                if (trim((string) $desc) === '') continue;
                $qty = (float) (Request::input('quantity')[$i] ?? 1);
                $price = (float) (Request::input('unit_price')[$i] ?? 0);
                $taxRate = (float) (Request::input('tax_rate')[$i] ?? 0);
                $lineTotal = $qty * $price;
                $taxAmount = $lineTotal * $taxRate / 100;
                $this->invoices->addItem($invoiceId, [
                    'description' => $desc,
                    'category' => Request::input('category')[$i] ?? 'other',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => round($taxAmount, 2),
                    'total' => round($lineTotal + $taxAmount, 2),
                ]);
            }
            $this->invoices->recalculate($invoiceId);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Invoice creation failed: ' . $e->getMessage());
            $this->back();
            return;
        }
        Audit::log('invoice.create', 'invoice', $invoiceId);
        Session::flash('success', 'Invoice created.');
        $this->redirect('/billing/' . $invoiceId);
    }

    public function show($params): void
    {
        $this->authorize('billing.view');
        $invoice = $this->invoices->find($params['id']);
        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            $this->redirect('/billing');
        }
        $this->view('billing/show', [
            'title' => $invoice['number'],
            'invoice' => $invoice,
            'items' => $this->invoices->items((int) $invoice['id']),
            'guest' => $invoice['guest_id'] ? (new Guest())->find($invoice['guest_id']) : null,
            'hotel' => App::db()->first('SELECT * FROM hotels WHERE id = ?', [$invoice['hotel_id']]),
            'payments' => (new Payment())->forInvoice((int) $invoice['id']),
        ]);
    }

    public function addPayment($params): void
    {
        $this->authorize('billing.manage');
        $invoice = $this->invoices->find($params['id']);
        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            $this->redirect('/billing');
        }
        $amount = (float) Request::input('amount');
        $type = Request::input('type', 'payment');
        (new Payment())->create([
            'hotel_id' => $invoice['hotel_id'],
            'invoice_id' => $invoice['id'],
            'reservation_id' => $invoice['reservation_id'],
            'guest_id' => $invoice['guest_id'],
            'amount' => $amount,
            'method' => Request::input('method', 'cash'),
            'gateway_ref' => Request::input('gateway_ref'),
            'type' => $type,
            'status' => 'success',
            'notes' => Request::input('notes'),
            'created_by' => Auth::id(),
        ]);
        $this->invoices->recalculate((int) $invoice['id']);

        // Keep the reservation's paid amount in sync.
        if ($invoice['reservation_id']) {
            $paid = (float) App::db()->scalar(
                "SELECT COALESCE(SUM(CASE WHEN type='refund' THEN -amount ELSE amount END),0)
                 FROM payments WHERE reservation_id = ? AND status='success'",
                [$invoice['reservation_id']]
            );
            (new Reservation())->update($invoice['reservation_id'], ['paid_amount' => $paid]);
        }
        Audit::log('payment.record', 'invoice', $invoice['id'], ['amount' => $amount, 'type' => $type]);
        Session::flash('success', ucfirst($type) . ' of ' . money($amount) . ' recorded.');
        $this->redirect('/billing/' . $invoice['id']);
    }

    /** Download the invoice as a real PDF. */
    public function pdf($params): void
    {
        $this->authorize('billing.view');
        $invoice = $this->invoices->find($params['id']);
        if (!$invoice) {
            http_response_code(404);
            echo 'Invoice not found';
            return;
        }
        $path = $this->renderPdf($invoice);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $invoice['number'] . '.pdf"');
        readfile($path);
        @unlink($path);
    }

    public function email($params): void
    {
        $this->authorize('billing.manage');
        $invoice = $this->invoices->find($params['id']);
        $guest = $invoice['guest_id'] ? (new Guest())->find($invoice['guest_id']) : null;
        if (!$guest || empty($guest['email'])) {
            Session::flash('error', 'Guest has no email on file.');
            $this->back();
            return;
        }
        $path = $this->renderPdf($invoice);
        $manager = new NotificationManager();
        $msg = $manager->renderTemplate((int) $invoice['hotel_id'], 'email', 'invoice', [
            'guest_name' => (new Guest())->fullName($guest),
            'number' => $invoice['number'],
            'balance' => money($invoice['balance']),
            'hotel_name' => App::db()->scalar('SELECT name FROM hotels WHERE id = ?', [$invoice['hotel_id']]),
        ], '<p>Please find your invoice attached.</p>');
        $result = $manager->send('email', (int) $invoice['hotel_id'], $guest['email'],
            $msg['subject'] ?: 'Your Invoice', $msg['body'], ['attachments' => [$path]]);
        @unlink($path);
        Audit::log('invoice.email', 'invoice', $invoice['id']);
        Session::flash($result['success'] ? 'success' : 'error',
            $result['success'] ? 'Invoice emailed.' : ('Email failed: ' . $result['error']));
        $this->back();
    }

    private function renderPdf(array $invoice): string
    {
        $hotel = App::db()->first('SELECT * FROM hotels WHERE id = ?', [$invoice['hotel_id']]);
        $guest = $invoice['guest_id'] ? (new Guest())->find($invoice['guest_id']) : null;
        $items = $this->invoices->items((int) $invoice['id']);

        $pdf = new PdfService();
        $pdf->heading($hotel['name'] ?? 'Invoice')
            ->line($hotel['address'] ?? '')
            ->line(trim(($hotel['city'] ?? '') . ' ' . ($hotel['pincode'] ?? '')))
            ->line('GSTIN: ' . ($hotel['gst_number'] ?? '-'))
            ->rule()
            ->line('TAX INVOICE', 13, 6)
            ->line('Invoice No: ' . $invoice['number'])
            ->line('Date: ' . date('d M Y', strtotime($invoice['issued_at'])))
            ->line('Bill To: ' . ($guest ? trim($guest['first_name'] . ' ' . $guest['last_name']) : 'Walk-in'))
            ->rule()
            ->line(sprintf('%-40s %6s %10s %8s %10s', 'Description', 'Qty', 'Rate', 'Tax%', 'Total'), 9);
        foreach ($items as $it) {
            $pdf->line(sprintf('%-40s %6s %10s %8s %10s',
                substr($it['description'], 0, 40), $it['quantity'],
                number_format((float) $it['unit_price'], 2), $it['tax_rate'],
                number_format((float) $it['total'], 2)), 9);
        }
        $pdf->rule()
            ->line('Subtotal:  Rs. ' . number_format((float) $invoice['subtotal'], 2))
            ->line('Tax:       Rs. ' . number_format((float) $invoice['tax_total'], 2))
            ->line('Discount:  Rs. ' . number_format((float) $invoice['discount'], 2))
            ->line('TOTAL:     Rs. ' . number_format((float) $invoice['total'], 2), 12)
            ->line('Paid:      Rs. ' . number_format((float) $invoice['paid'], 2))
            ->line('Balance:   Rs. ' . number_format((float) $invoice['balance'], 2), 12)
            ->rule()
            ->line('Thank you for your stay!');

        $file = App::config('paths.cache') . '/' . $invoice['number'] . '.pdf';
        return $pdf->save($file);
    }
}
