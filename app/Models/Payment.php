<?php
namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    protected string $table = 'payments';
    protected array $fillable = [
        'hotel_id', 'invoice_id', 'reservation_id', 'guest_id', 'amount',
        'method', 'gateway', 'gateway_ref', 'type', 'status', 'notes', 'created_by',
    ];

    public function forInvoice(int $invoiceId): array
    {
        return $this->all('invoice_id = ?', [$invoiceId], 'created_at DESC');
    }
}
