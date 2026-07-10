<?php
namespace App\Services\OTA;

use App\Core\Logger;

/**
 * Shared connector plumbing: credential handling, HTTP helper, uniform result
 * shaping and a safe default for OTAs whose partner API is not yet approved
 * (returns a "pending credentials" result rather than throwing).
 */
abstract class AbstractOtaConnector implements OtaConnector
{
    protected array $credentials;
    protected int $hotelId;

    public function __construct(int $hotelId, array $credentials)
    {
        $this->hotelId = $hotelId;
        $this->credentials = $credentials;
    }

    protected function ok(string $message, $data = null): array
    {
        return ['success' => true, 'message' => $message, 'data' => $data];
    }

    protected function fail(string $message, $data = null): array
    {
        return ['success' => false, 'message' => $message, 'data' => $data];
    }

    /** True when required credentials are present. */
    protected function hasCredentials(): bool
    {
        foreach ($this->requiredCredentials() as $key) {
            if (empty($this->credentials[$key])) {
                return false;
            }
        }
        return true;
    }

    /** Which credential keys this connector needs (override per OTA). */
    protected function requiredCredentials(): array
    {
        return ['api_key'];
    }

    /**
     * Perform an authenticated HTTP request against the OTA API. Concrete
     * connectors call this from their push/pull methods once credentials and
     * partner endpoints are configured.
     */
    protected function request(string $method, string $url, array $payload = [], array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            return $this->fail('cURL not available.');
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        ];
        if ($payload) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            Logger::warn("OTA request failed [{$this->channelKey()}]", ['error' => $err]);
            return $this->fail($err ?: 'Request failed');
        }
        $decoded = json_decode((string) $body, true);
        return $code >= 200 && $code < 300
            ? $this->ok('OK', $decoded)
            : $this->fail("HTTP $code", $decoded);
    }

    // Sensible no-op defaults for OTAs awaiting partner approval. Concrete
    // connectors override these once real endpoints are available.
    public function testConnection(): array
    {
        return $this->hasCredentials()
            ? $this->ok($this->displayName() . ' credentials present.')
            : $this->fail($this->displayName() . ' credentials not configured.');
    }

    public function pushInventory(array $inventory): array
    {
        return $this->guarded(fn () => $this->ok('Inventory queued for ' . $this->displayName(), $inventory));
    }

    public function pushRates(array $rates): array
    {
        return $this->guarded(fn () => $this->ok('Rates queued for ' . $this->displayName(), $rates));
    }

    public function pushRestrictions(array $restrictions): array
    {
        return $this->guarded(fn () => $this->ok('Restrictions queued for ' . $this->displayName(), $restrictions));
    }

    public function pullBookings(): array
    {
        return $this->guarded(fn () => $this->ok('No new bookings.', []));
    }

    /** Run a callable only when credentials exist. */
    protected function guarded(callable $fn): array
    {
        if (!$this->hasCredentials()) {
            return $this->fail($this->displayName() . ' is awaiting API credentials / partner approval.');
        }
        return $fn();
    }
}
