<?php
namespace App\Services\Payment;

/**
 * Razorpay gateway. Creates an order via the Orders API and verifies the
 * payment signature on callback. Requires razorpay_key + razorpay_secret in
 * the hotel's payment settings.
 */
class RazorpayGateway extends AbstractGateway
{
    public function key(): string { return 'razorpay'; }
    public function displayName(): string { return 'Razorpay'; }

    public function createOrder(float $amount, string $currency, array $meta = []): array
    {
        if (!$this->configured(['razorpay_key', 'razorpay_secret'])) {
            return $this->fail('Razorpay keys not configured.');
        }
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->config['razorpay_key'] . ':' . $this->config['razorpay_secret'],
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'amount' => (int) round($amount * 100), // paise
                'currency' => $currency,
                'receipt' => $meta['receipt'] ?? ('rcpt_' . time()),
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false || $code >= 400) {
            return $this->fail('Razorpay order creation failed.');
        }
        $data = json_decode((string) $res, true);
        return $this->ok(['reference' => $data['id'] ?? null]);
    }

    public function verify(array $payload): array
    {
        // Signature = HMAC_SHA256(order_id + "|" + payment_id, secret)
        $expected = hash_hmac(
            'sha256',
            ($payload['razorpay_order_id'] ?? '') . '|' . ($payload['razorpay_payment_id'] ?? ''),
            $this->config['razorpay_secret'] ?? ''
        );
        if (!empty($payload['razorpay_signature']) && hash_equals($expected, $payload['razorpay_signature'])) {
            return $this->ok(['reference' => $payload['razorpay_payment_id']]);
        }
        return $this->fail('Signature verification failed.');
    }
}
