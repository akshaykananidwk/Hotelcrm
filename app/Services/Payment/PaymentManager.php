<?php
namespace App\Services\Payment;

use App\Models\Setting;

/**
 * Payment gateway registry. Resolves a configured gateway by key. Register new
 * gateways here — the rest of the system depends only on the PaymentGateway
 * interface, so nothing else changes.
 */
class PaymentManager
{
    private const GATEWAYS = [
        'cash'     => CashGateway::class,
        'bank'     => CashGateway::class,
        'razorpay' => RazorpayGateway::class,
        'payu'     => PayUGateway::class,
        'phonepe'  => PhonePeGateway::class,
    ];

    public function gateway(string $key, ?int $hotelId): PaymentGateway
    {
        $class = self::GATEWAYS[$key] ?? CashGateway::class;
        $config = (new Setting())->group('payment', $hotelId);
        return new $class($config);
    }

    /** List of gateway keys => display names for settings dropdowns. */
    public function available(): array
    {
        $out = [];
        foreach (self::GATEWAYS as $key => $class) {
            $out[$key] = (new $class())->displayName();
        }
        return $out;
    }
}
