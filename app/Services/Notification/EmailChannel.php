<?php
namespace App\Services\Notification;

use App\Core\Logger;

/**
 * SMTP email channel implemented with a minimal raw SMTP client (no external
 * dependency). Supports STARTTLS/SSL, HTML bodies and file attachments so PDF
 * invoices can be emailed. Configure via the hotel's SMTP settings group.
 */
class EmailChannel implements NotificationChannel
{
    private array $cfg;

    public function __construct(array $config)
    {
        $this->cfg = $config;
    }

    public function key(): string
    {
        return 'email';
    }

    public function send(string $recipient, string $subject, string $body, array $options = []): array
    {
        $host = $this->cfg['host'] ?? '';
        if ($host === '') {
            return ['success' => false, 'error' => 'SMTP not configured.', 'reference' => null];
        }
        try {
            $this->smtpSend($recipient, $subject, $body, $options['attachments'] ?? []);
            return ['success' => true, 'error' => null, 'reference' => null];
        } catch (\Throwable $e) {
            Logger::warn('Email send failed', ['to' => $recipient, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'reference' => null];
        }
    }

    private function smtpSend(string $to, string $subject, string $htmlBody, array $attachments): void
    {
        $host = $this->cfg['host'];
        $port = (int) ($this->cfg['port'] ?? 587);
        $user = $this->cfg['username'] ?? '';
        $pass = $this->cfg['password'] ?? '';
        $fromEmail = $this->cfg['from_email'] ?? ('no-reply@' . ($host ?: 'localhost'));
        $fromName = $this->cfg['from_name'] ?? 'HotelCRM';
        $secure = $this->cfg['encryption'] ?? ($port === 465 ? 'ssl' : 'tls');

        $transport = $secure === 'ssl' ? "ssl://$host" : $host;
        $fp = @stream_socket_client("$transport:$port", $errno, $errstr, 20);
        if (!$fp) {
            throw new \RuntimeException("SMTP connect failed: $errstr");
        }
        $read = function () use ($fp) { return fgets($fp, 515); };
        $cmd = function (string $c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };

        $read();
        $cmd("EHLO hotelcrm");
        if ($secure === 'tls') {
            $cmd("STARTTLS");
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd("EHLO hotelcrm");
        }
        if ($user !== '') {
            $cmd("AUTH LOGIN");
            $cmd(base64_encode($user));
            $auth = $cmd(base64_encode($pass));
            if (!str_starts_with($auth, '235')) {
                throw new \RuntimeException('SMTP auth failed.');
            }
        }
        $cmd("MAIL FROM:<$fromEmail>");
        $cmd("RCPT TO:<$to>");
        $cmd("DATA");

        $boundary = 'bnd_' . bin2hex(random_bytes(8));
        $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        foreach ($attachments as $file) {
            if (!is_file($file)) continue;
            $name = basename($file);
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"$name\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
            $message .= chunk_split(base64_encode((string) file_get_contents($file))) . "\r\n";
        }
        $message .= "--$boundary--\r\n";

        fwrite($fp, $headers . "\r\n" . $message . "\r\n.\r\n");
        $read();
        $cmd("QUIT");
        fclose($fp);
    }
}
