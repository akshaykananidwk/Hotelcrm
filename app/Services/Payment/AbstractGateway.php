<?php
namespace App\Services\Payment;

abstract class AbstractGateway implements PaymentGateway
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    protected function ok(array $extra = []): array
    {
        return array_merge(['success' => true, 'reference' => null, 'redirect_url' => null, 'error' => null], $extra);
    }

    protected function fail(string $error): array
    {
        return ['success' => false, 'reference' => null, 'redirect_url' => null, 'error' => $error];
    }

    protected function configured(array $keys): bool
    {
        foreach ($keys as $k) {
            if (empty($this->config[$k])) {
                return false;
            }
        }
        return true;
    }
}
