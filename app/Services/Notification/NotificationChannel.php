<?php
namespace App\Services\Notification;

/**
 * Contract every notification channel implements. New channels (SMS, push,
 * Telegram…) can be added by implementing this interface and registering the
 * channel in NotificationManager — the core never changes.
 */
interface NotificationChannel
{
    /** Machine key, e.g. 'whatsapp'. */
    public function key(): string;

    /**
     * Deliver a message.
     *
     * @param string $recipient email address or phone number
     * @param string $subject   used by channels that support it (email)
     * @param string $body      message body (HTML for email, text otherwise)
     * @param array  $options   channel-specific extras (e.g. media_url, attachments)
     * @return array ['success' => bool, 'error' => ?string, 'reference' => ?string]
     */
    public function send(string $recipient, string $subject, string $body, array $options = []): array;
}
