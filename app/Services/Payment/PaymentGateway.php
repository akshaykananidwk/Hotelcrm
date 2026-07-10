<?php
namespace App\Services\Payment;

/**
 * Payment gateway contract. New gateways implement this and register in
 * PaymentManager::GATEWAYS — the billing core never changes (open/closed).
 */
interface PaymentGateway
{
    public function key(): string;
    public function displayName(): string;

    /**
     * Create a payment intent / order.
     * @return array ['success'=>bool,'reference'=>?string,'redirect_url'=>?string,'error'=>?string]
     */
    public function createOrder(float $amount, string $currency, array $meta = []): array;

    /** Verify a gateway callback / webhook signature + status. */
    public function verify(array $payload): array;
}
