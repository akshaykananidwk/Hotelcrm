<?php
namespace App\Services\Notification;

/** In-app notifications are simply persisted; delivery is the DB write itself. */
class InAppChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'in_app';
    }

    public function send(string $recipient, string $subject, string $body, array $options = []): array
    {
        // Persistence handled by NotificationManager; nothing to transmit.
        return ['success' => true, 'error' => null, 'reference' => null];
    }
}
