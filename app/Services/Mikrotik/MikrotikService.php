<?php
namespace App\Services\Mikrotik;

use App\Core\App;
use App\Core\Logger;
use App\Models\Setting;

/**
 * MikroTik RouterOS integration for guest Wi-Fi. Speaks the RouterOS binary API
 * (default port 8728) to create hotspot users, assign profiles/bandwidth and
 * disable accounts at check-out. Router credentials are configured per hotel in
 * the MikroTik settings group.
 *
 * The API client is intentionally dependency-free (raw socket) so it runs on
 * stock PHP. When a router is unreachable/unconfigured, methods return a
 * failure result rather than throwing, and the voucher is still stored locally.
 */
class MikrotikService
{
    private array $cfg;
    private int $hotelId;

    public function __construct(int $hotelId)
    {
        $this->hotelId = $hotelId;
        $this->cfg = (new Setting())->group('mikrotik', $hotelId);
    }

    /**
     * Create a Wi-Fi voucher for a guest/reservation. Stores it locally and,
     * if the router is configured, provisions the hotspot user too.
     */
    public function createVoucher(?int $reservationId, ?\DateTimeInterface $validUntil = null, ?string $profile = null): array
    {
        $username = 'g' . random_int(10000, 99999);
        $password = strtoupper(bin2hex(random_bytes(3)));
        $profile = $profile ?: ($this->cfg['default_profile'] ?? 'guest');

        $voucherId = App::db()->insert('wifi_vouchers', [
            'hotel_id' => $this->hotelId,
            'reservation_id' => $reservationId,
            'username' => $username,
            'password' => $password,
            'profile' => $profile,
            'bandwidth' => $this->cfg['bandwidth'] ?? null,
            'valid_until' => $validUntil?->format('Y-m-d H:i:s'),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $provisioned = $this->apiCommand('/ip/hotspot/user/add', [
            'name' => $username,
            'password' => $password,
            'profile' => $profile,
        ]);

        return [
            'success' => $provisioned['success'],
            'voucher_id' => $voucherId,
            'username' => $username,
            'password' => $password,
            'message' => $provisioned['message'],
        ];
    }

    /** Disable a guest's Wi-Fi at check-out. */
    public function disableVoucher(int $voucherId): array
    {
        $voucher = App::db()->first('SELECT * FROM wifi_vouchers WHERE id = ?', [$voucherId]);
        if (!$voucher) {
            return ['success' => false, 'message' => 'Voucher not found.'];
        }
        App::db()->update('wifi_vouchers', ['status' => 'disabled'], 'id = :id', ['id' => $voucherId]);
        return $this->apiCommand('/ip/hotspot/user/disable', ['numbers' => $voucher['username']]);
    }

    /** Test connectivity + login to the router. */
    public function testConnection(): array
    {
        return $this->apiCommand('/system/identity/print', []);
    }

    /**
     * Minimal RouterOS API call. Returns ['success'=>bool,'message'=>string].
     * Falls back to a "not configured" result when host/credentials are absent.
     */
    private function apiCommand(string $command, array $params): array
    {
        $host = $this->cfg['host'] ?? '';
        if ($host === '') {
            return ['success' => false, 'message' => 'MikroTik router not configured (voucher stored locally).'];
        }
        $port = (int) ($this->cfg['port'] ?? 8728);
        $user = $this->cfg['username'] ?? 'admin';
        $pass = $this->cfg['password'] ?? '';

        try {
            $sock = @fsockopen($host, $port, $errno, $errstr, 5);
            if (!$sock) {
                throw new \RuntimeException("Connect failed: $errstr");
            }
            stream_set_timeout($sock, 5);
            // RouterOS 6.43+ login: send /login with name+password directly.
            $this->write($sock, ['/login', '=name=' . $user, '=password=' . $pass]);
            $login = $this->read($sock);
            if (!in_array('!done', $login, true)) {
                fclose($sock);
                throw new \RuntimeException('RouterOS login failed.');
            }
            $words = [$command];
            foreach ($params as $k => $v) {
                $words[] = "=$k=$v";
            }
            $this->write($sock, $words);
            $response = $this->read($sock);
            fclose($sock);
            $ok = in_array('!done', $response, true) && !in_array('!trap', $response, true);
            return ['success' => $ok, 'message' => $ok ? 'OK' : 'RouterOS returned an error.'];
        } catch (\Throwable $e) {
            Logger::warn('MikroTik API error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Encode + send an API sentence. */
    private function write($sock, array $words): void
    {
        foreach ($words as $w) {
            fwrite($sock, $this->encodeLength(strlen($w)) . $w);
        }
        fwrite($sock, chr(0));
    }

    /** Read an API response sentence into an array of words. */
    private function read($sock): array
    {
        $out = [];
        while (true) {
            $len = $this->decodeLength($sock);
            if ($len === 0) {
                if (empty($out)) continue;
                break;
            }
            $out[] = stream_get_contents($sock, $len);
            $meta = stream_get_meta_data($sock);
            if ($meta['timed_out']) break;
        }
        return $out;
    }

    private function encodeLength(int $len): string
    {
        if ($len < 0x80) {
            return chr($len);
        }
        if ($len < 0x4000) {
            $len |= 0x8000;
            return chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
        }
        // Larger lengths are rare for our commands; support up to 3 bytes.
        $len |= 0xC00000;
        return chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
    }

    private function decodeLength($sock): int
    {
        $byte = ord(fread($sock, 1));
        if ($byte < 0x80) {
            return $byte;
        }
        if (($byte & 0xC0) === 0x80) {
            return (($byte & 0x3F) << 8) + ord(fread($sock, 1));
        }
        // Fallback for 3-byte lengths.
        return (($byte & 0x1F) << 16) + (ord(fread($sock, 1)) << 8) + ord(fread($sock, 1));
    }
}
