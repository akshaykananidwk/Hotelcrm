<?php
namespace App\Controllers\Api;

use App\Core\Request;
use App\Models\Invoice;
use App\Models\Payment;

class BillingApiController extends ApiController
{
    public function index(): void
    {
        $result = (new Invoice())->withGuest($this->hotelId(), max(1, (int) Request::get('page', 1)));
        $this->success($result);
    }

    public function show($params): void
    {
        $invoice = (new Invoice())->find($params['id']);
        if (!$invoice || (int) $invoice['hotel_id'] !== $this->hotelId()) {
            $this->error('Invoice not found.', 404);
            return;
        }
        $invoice['items'] = (new Invoice())->items((int) $invoice['id']);
        $invoice['payments'] = (new Payment())->forInvoice((int) $invoice['id']);
        $this->success($invoice);
    }

    public function payment(): void
    {
        $body = Request::all();
        if (empty($body['invoice_id']) || empty($body['amount'])) {
            $this->error('invoice_id and amount are required.', 422);
            return;
        }
        $invoice = (new Invoice())->find($body['invoice_id']);
        if (!$invoice || (int) $invoice['hotel_id'] !== $this->hotelId()) {
            $this->error('Invoice not found.', 404);
            return;
        }
        (new Payment())->create([
            'hotel_id' => $invoice['hotel_id'],
            'invoice_id' => $invoice['id'],
            'reservation_id' => $invoice['reservation_id'],
            'guest_id' => $invoice['guest_id'],
            'amount' => (float) $body['amount'],
            'method' => $body['method'] ?? 'cash',
            'gateway_ref' => $body['gateway_ref'] ?? null,
            'type' => $body['type'] ?? 'payment',
            'status' => 'success',
        ]);
        (new Invoice())->recalculate((int) $invoice['id']);
        $this->success((new Invoice())->find($invoice['id']));
    }
}
