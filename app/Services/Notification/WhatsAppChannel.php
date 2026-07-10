<?php
namespace App\Services\Notification;

use App\Core\Logger;

/**
 * WhatsApp channel backed by the bulk.akdwk.in HTTP gateway.
 *
 * Text:  GET /api.php?number=91XXXXXXXXXX&message=...&session_id=...&api_key=...
 * Media: GET /api.php?number=...&message=Caption&media_url=https://.../img.jpg&session_id=...&api_key=...
 *
 * Credentials (api_url, session_id, api_key) are read from the hotel's
 * WhatsApp settings group and injected by NotificationManager.
 */
class WhatsAppChannel implements NotificationChannel
{
    private string $apiUrl;
    private string $sessionId;
    private string $apiKey;

    public function __construct(array $config)
    {
        $this->apiUrl    = $config['api_url'] ?? 'https://bulk.akdwk.in/api.php';
        $this->sessionId = $config['session_id'] ?? '';
        $this->apiKey    = $config['api_key'] ?? '';
    }

    public function key(): string
    {
        return 'whatsapp';
    }

    public function send(string $recipient, string $subject, string $body, array $options = []): array
    {
        if ($this->sessionId === '' || $this->apiKey === '') {
            return ['success' => false, 'error' => 'WhatsApp credentials not configured.', 'reference' => null];
        }

        $number = $this->normalizeNumber($recipient);
        $query = [
            'number'     => $number,
            'message'    => $body,
            'session_id' => $this->sessionId,
            'api_key'    => $this->apiKey,
        ];
        if (!empty($options['media_url'])) {
            $query['media_url'] = $options['media_url'];
        }

        $url = $this->apiUrl . '?' . http_build_query($query);
        [$ok, $response, $error] = $this->httpGet($url);

        if (!$ok) {
            Logger::warn('WhatsApp send failed', ['to' => $number, 'error' => $error]);
            return ['success' => false, 'error' => $error, 'reference' => null];
        }
        return ['success' => true, 'error' => null, 'reference' => substr((string) $response, 0, 120)];
    }

    /** Strip non-digits and ensure a country code (default India, 91). */
    private function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);
        if (strlen($digits) === 10) {
            $digits = '91' . $digits;
        }
        return $digits;
    }

    /** Thin cURL wrapper (falls back to file_get_contents). */
    private function httpGet(string $url): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $res = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res === false) {
                return [false, null, $err ?: 'Request failed'];
            }
            return [$code >= 200 && $code < 300, $res, $code >= 400 ? "HTTP $code" : null];
        }
        $res = @file_get_contents($url);
        return $res === false ? [false, null, 'Request failed'] : [true, $res, null];
    }
}
