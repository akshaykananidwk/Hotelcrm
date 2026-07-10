<?php
namespace App\Services\Payment;

/**
 * PhonePe gateway (PG Standard checkout). Builds the base64+X-VERIFY signed
 * request. Configure phonepe_merchant_id + phonepe_salt (+ phonepe_salt_index).
 */
class PhonePeGateway extends AbstractGateway
{
    public function key(): string { return 'phonepe'; }
    public function displayName(): string { return 'PhonePe'; }

    public function createOrder(float $amount, string $currency, array $meta = []): array
    {
        if (!$this->configured(['phonepe_merchant_id', 'phonepe_salt'])) {
            return $this->fail('PhonePe credentials not configured.');
        }
        $saltIndex = $this->config['phonepe_salt_index'] ?? '1';
        $txnId = 'PP' . strtoupper(bin2hex(random_bytes(6)));
        $payload = [
            'merchantId' => $this->config['phonepe_merchant_id'],
            'merchantTransactionId' => $txnId,
            'amount' => (int) round($amount * 100),
            'redirectUrl' => $meta['redirect_url'] ?? '',
            'paymentInstrument' => ['type' => 'PAY_PAGE'],
        ];
        $base64 = base64_encode(json_encode($payload));
        $xVerify = hash('sha256', $base64 . '/pg/v1/pay' . $this->config['phonepe_salt']) . '###' . $saltIndex;
        return $this->ok([
            'reference' => $txnId,
            'data' => ['request' => $base64, 'x_verify' => $xVerify],
        ]);
    }

    public function verify(array $payload): array
    {
        // PhonePe returns an X-VERIFY header the caller passes back for checking.
        $response = $payload['response'] ?? '';
        $received = $payload['x_verify'] ?? '';
        $expected = hash('sha256', $response . $this->config['phonepe_salt']) . '###' . ($this->config['phonepe_salt_index'] ?? '1');
        if ($received !== '' && hash_equals($expected, $received)) {
            return $this->ok(['reference' => $payload['transaction_id'] ?? null]);
        }
        return $this->fail('PhonePe verification failed.');
    }
}
