<?php
namespace App\Services\Payment;

/** Offline cash / bank-transfer "gateway" — always succeeds, recorded manually. */
class CashGateway extends AbstractGateway
{
    public function key(): string { return 'cash'; }
    public function displayName(): string { return 'Cash / Bank Transfer'; }

    public function createOrder(float $amount, string $currency, array $meta = []): array
    {
        return $this->ok(['reference' => 'CASH-' . strtoupper(bin2hex(random_bytes(4)))]);
    }

    public function verify(array $payload): array
    {
        return $this->ok(['reference' => $payload['reference'] ?? null]);
    }
}
