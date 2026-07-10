<?php
namespace App\Services\Payment;

/** PayU gateway (hash-based redirect flow). Configure payu_key + payu_salt. */
class PayUGateway extends AbstractGateway
{
    public function key(): string { return 'payu'; }
    public function displayName(): string { return 'PayU'; }

    public function createOrder(float $amount, string $currency, array $meta = []): array
    {
        if (!$this->configured(['payu_key', 'payu_salt'])) {
            return $this->fail('PayU credentials not configured.');
        }
        $txnid = 'PAYU' . strtoupper(bin2hex(random_bytes(5)));
        $key = $this->config['payu_key'];
        $salt = $this->config['payu_salt'];
        $productinfo = $meta['productinfo'] ?? 'Hotel Booking';
        $firstname = $meta['firstname'] ?? 'Guest';
        $email = $meta['email'] ?? 'guest@example.com';
        // Standard PayU hash sequence.
        $hashSeq = "$key|$txnid|$amount|$productinfo|$firstname|$email|||||||||||$salt";
        $hash = strtolower(hash('sha512', $hashSeq));
        return $this->ok([
            'reference' => $txnid,
            'redirect_url' => 'https://secure.payu.in/_payment',
            'error' => null,
        ] + ['data' => compact('key', 'txnid', 'amount', 'productinfo', 'firstname', 'email', 'hash')]);
    }

    public function verify(array $payload): array
    {
        $salt = $this->config['payu_salt'] ?? '';
        $key = $this->config['payu_key'] ?? '';
        $status = $payload['status'] ?? '';
        $seq = "$salt|$status|||||||||||{$payload['email']}|{$payload['firstname']}|{$payload['productinfo']}|{$payload['amount']}|{$payload['txnid']}|$key";
        $expected = strtolower(hash('sha512', $seq));
        if (!empty($payload['hash']) && hash_equals($expected, $payload['hash'])) {
            return $this->ok(['reference' => $payload['txnid'] ?? null]);
        }
        return $this->fail('PayU hash verification failed.');
    }
}
