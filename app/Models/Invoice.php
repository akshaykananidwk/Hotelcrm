<?php
namespace App\Models;

use App\Core\Model;

class Invoice extends Model
{
    protected string $table = 'invoices';
    protected array $fillable = [
        'hotel_id', 'reservation_id', 'guest_id', 'number', 'subtotal',
        'tax_total', 'discount', 'total', 'paid', 'balance', 'status',
        'gst_number', 'place_of_supply', 'notes', 'created_by',
    ];

    public function generateNumber(): string
    {
        return 'INV-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function items(int $invoiceId): array
    {
        return $this->db()->all('SELECT * FROM invoice_items WHERE invoice_id = ?', [$invoiceId]);
    }

    public function addItem(int $invoiceId, array $item): int
    {
        return $this->db()->insert('invoice_items', array_merge(['invoice_id' => $invoiceId], $item));
    }

    /** Recalculate totals from items + payments and persist. */
    public function recalculate(int $invoiceId): void
    {
        $items = $this->items($invoiceId);
        $subtotal = 0.0;
        $tax = 0.0;
        foreach ($items as $it) {
            $subtotal += (float) $it['unit_price'] * (float) $it['quantity'];
            $tax += (float) $it['tax_amount'];
        }
        $invoice = $this->find($invoiceId);
        $discount = (float) ($invoice['discount'] ?? 0);
        $total = $subtotal + $tax - $discount;
        $paid = (float) $this->db()->scalar(
            "SELECT COALESCE(SUM(CASE WHEN type='refund' THEN -amount ELSE amount END),0)
             FROM payments WHERE invoice_id = ? AND status = 'success'",
            [$invoiceId]
        );
        $balance = round($total - $paid, 2);
        $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
        $this->update($invoiceId, [
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'total' => round($total, 2),
            'paid' => round($paid, 2),
            'balance' => $balance,
            'status' => $status,
        ]);
    }

    public function withGuest(int $hotelId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) $this->db()->scalar('SELECT COUNT(*) FROM invoices WHERE hotel_id = ?', [$hotelId]);
        $data = $this->db()->all(
            "SELECT i.*, CONCAT(g.first_name,' ',COALESCE(g.last_name,'')) AS guest_name
             FROM invoices i LEFT JOIN guests g ON g.id = i.guest_id
             WHERE i.hotel_id = ? ORDER BY i.issued_at DESC LIMIT $perPage OFFSET $offset",
            [$hotelId]
        );
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }
}
