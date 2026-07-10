<?php
namespace App\Services\Notification;

use App\Core\App;
use App\Core\Logger;
use App\Models\Setting;

/**
 * Unified notification engine. Resolves the right channel, renders templates
 * with {{placeholders}}, records every message in the `notifications` table
 * and returns delivery results. New channels register in channelFor().
 */
class NotificationManager
{
    private Setting $settings;

    public function __construct()
    {
        $this->settings = new Setting();
    }

    /** Instantiate a channel with the hotel's configured credentials. */
    public function channelFor(string $channel, ?int $hotelId): NotificationChannel
    {
        return match ($channel) {
            'whatsapp' => new WhatsAppChannel($this->settings->group('whatsapp', $hotelId)),
            'email'    => new EmailChannel($this->settings->group('smtp', $hotelId)),
            default    => new InAppChannel(),
        };
    }

    /**
     * Send a rendered message on a channel, logging it to the DB.
     */
    public function send(string $channel, ?int $hotelId, string $recipient, string $subject, string $body, array $options = []): array
    {
        $impl = $this->channelFor($channel, $hotelId);
        $result = $impl->send($recipient, $subject, $body, $options);

        try {
            App::db()->insert('notifications', [
                'hotel_id'  => $hotelId,
                'channel'   => $channel,
                'title'     => $subject ?: substr($body, 0, 120),
                'body'      => $body,
                'recipient' => $recipient,
                'status'    => $result['success'] ? 'sent' : 'failed',
                'error'     => $result['error'],
                'created_at'=> date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Logger::warn('Notification log failed: ' . $e->getMessage());
        }
        return $result;
    }

    /** Render a stored template for an event, or fall back to $default. */
    public function renderTemplate(?int $hotelId, string $channel, string $eventKey, array $vars, string $default = ''): array
    {
        $tpl = App::db()->first(
            'SELECT subject, body FROM message_templates
             WHERE channel = ? AND event_key = ? AND (hotel_id = ? OR hotel_id IS NULL) AND is_active = 1
             ORDER BY hotel_id DESC LIMIT 1',
            [$channel, $eventKey, $hotelId]
        );
        $subject = $tpl['subject'] ?? '';
        $body = $tpl['body'] ?? $default;
        return [
            'subject' => $this->interpolate($subject, $vars),
            'body'    => $this->interpolate($body, $vars),
        ];
    }

    private function interpolate(string $text, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $text = str_replace('{{' . $k . '}}', (string) $v, $text);
        }
        return $text;
    }

    // -----------------------------------------------------------------------
    // High-level event helpers
    // -----------------------------------------------------------------------

    public function sendReservationConfirmation(int $reservationId): void
    {
        $row = App::db()->first(
            "SELECT r.*, CONCAT(g.first_name,' ',COALESCE(g.last_name,'')) AS guest_name,
                    g.phone AS guest_phone, g.email AS guest_email, h.name AS hotel_name,
                    h.currency_symbol
             FROM reservations r
             JOIN guests g ON g.id = r.guest_id
             JOIN hotels h ON h.id = r.hotel_id
             WHERE r.id = ?",
            [$reservationId]
        );
        if (!$row) {
            return;
        }
        $vars = [
            'guest_name' => $row['guest_name'],
            'code'       => $row['code'],
            'hotel_name' => $row['hotel_name'],
            'check_in'   => $row['check_in'],
            'check_out'  => $row['check_out'],
            'total'      => $row['currency_symbol'] . number_format((float) $row['total_amount'], 2),
        ];
        $hotelId = (int) $row['hotel_id'];

        // WhatsApp
        if (!empty($row['guest_phone'])) {
            $msg = $this->renderTemplate($hotelId, 'whatsapp', 'booking_confirmation', $vars,
                'Hi {{guest_name}}, your booking {{code}} is confirmed.');
            $this->send('whatsapp', $hotelId, $row['guest_phone'], '', $msg['body']);
        }
        // Email
        if (!empty($row['guest_email'])) {
            $msg = $this->renderTemplate($hotelId, 'email', 'booking_confirmation', $vars,
                '<p>Your booking {{code}} is confirmed.</p>');
            $this->send('email', $hotelId, $row['guest_email'], $msg['subject'] ?: 'Booking Confirmed', $msg['body']);
        }
        // In-app for staff
        $this->send('in_app', $hotelId, '', 'New reservation ' . $row['code'],
            $row['guest_name'] . ' — ' . $row['check_in'] . ' to ' . $row['check_out']);
    }
}
