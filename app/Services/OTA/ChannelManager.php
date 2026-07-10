<?php
namespace App\Services\OTA;

use App\Core\App;
use App\Core\Logger;
use App\Models\OtaChannel;

/**
 * Channel Manager orchestrator. Resolves the right connector for a channel,
 * enqueues asynchronous sync jobs and processes the queue with retry/backoff.
 * Run the worker via: php cli/worker.php  (cron every minute).
 */
class ChannelManager
{
    /** Channels served by the generic REST connector. */
    private const GENERIC = [
        'makemytrip'  => 'MakeMyTrip',
        'goibibo'     => 'Goibibo',
        'agoda'       => 'Agoda',
        'expedia'     => 'Expedia',
        'airbnb'      => 'Airbnb',
        'hotels_com'  => 'Hotels.com',
        'trip_com'    => 'Trip.com',
        'hostelworld' => 'Hostelworld',
    ];

    public function connector(int $hotelId, string $channel): OtaConnector
    {
        $creds = $this->credentials($hotelId, $channel);
        if ($channel === 'booking_com') {
            return new BookingComConnector($hotelId, $creds);
        }
        $name = self::GENERIC[$channel] ?? ucfirst($channel);
        return new GenericOtaConnector($hotelId, $creds, $channel, $name);
    }

    private function credentials(int $hotelId, string $channel): array
    {
        $row = (new OtaChannel())->byChannel($hotelId, $channel);
        if (!$row || empty($row['credentials'])) {
            return [];
        }
        $decoded = json_decode($row['credentials'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /** Queue a sync job (async). */
    public function enqueue(int $hotelId, string $channel, string $jobType, array $payload = []): int
    {
        return App::db()->insert('sync_jobs', [
            'hotel_id' => $hotelId,
            'channel' => $channel,
            'job_type' => $jobType,
            'payload' => $payload ? json_encode($payload) : null,
            'status' => 'queued',
            'run_after' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Enqueue a full sync (inventory + rates + restrictions + bookings). */
    public function enqueueFullSync(int $hotelId, string $channel): void
    {
        foreach (['inventory', 'rates', 'restrictions', 'bookings'] as $type) {
            $this->enqueue($hotelId, $channel, $type);
        }
    }

    /**
     * Process up to $limit queued jobs. Returns the number processed.
     * Failed jobs are retried with exponential backoff up to max_attempts.
     */
    public function processQueue(int $limit = 20): int
    {
        $db = App::db();
        $jobs = $db->all(
            "SELECT * FROM sync_jobs
             WHERE status IN ('queued','failed') AND attempts < max_attempts
               AND (run_after IS NULL OR run_after <= NOW())
             ORDER BY id ASC LIMIT $limit"
        );
        $processed = 0;
        foreach ($jobs as $job) {
            $db->update('sync_jobs', ['status' => 'running', 'attempts' => $job['attempts'] + 1], 'id = :id', ['id' => $job['id']]);
            try {
                $connector = $this->connector((int) $job['hotel_id'], $job['channel']);
                $payload = $job['payload'] ? json_decode($job['payload'], true) : [];
                $result = match ($job['job_type']) {
                    'inventory'    => $connector->pushInventory($payload),
                    'rates'        => $connector->pushRates($payload),
                    'restrictions' => $connector->pushRestrictions($payload),
                    'bookings'     => $this->importBookings((int) $job['hotel_id'], $connector),
                    'full'         => $connector->testConnection(),
                    default        => ['success' => false, 'message' => 'Unknown job type'],
                };

                if ($result['success']) {
                    $db->update('sync_jobs', ['status' => 'success', 'error' => null], 'id = :id', ['id' => $job['id']]);
                    $this->markChannel((int) $job['hotel_id'], $job['channel'], 'success');
                } else {
                    $this->retryOrFail($job, $result['message']);
                }
            } catch (\Throwable $e) {
                Logger::error('Sync job failed', ['job' => $job['id'], 'error' => $e->getMessage()]);
                $this->retryOrFail($job, $e->getMessage());
            }
            $processed++;
        }
        return $processed;
    }

    private function importBookings(int $hotelId, OtaConnector $connector): array
    {
        $result = $connector->pullBookings();
        // Real implementation maps $result['data'] into reservations here.
        return $result;
    }

    private function retryOrFail(array $job, string $error): void
    {
        $attempts = $job['attempts'] + 1;
        $backoff = min(3600, (2 ** $attempts) * 30); // exponential, capped 1h
        App::db()->update('sync_jobs', [
            'status' => $attempts >= $job['max_attempts'] ? 'failed' : 'queued',
            'error' => $error,
            'run_after' => date('Y-m-d H:i:s', time() + $backoff),
        ], 'id = :id', ['id' => $job['id']]);
        $this->markChannel((int) $job['hotel_id'], $job['channel'], 'failed');
    }

    private function markChannel(int $hotelId, string $channel, string $status): void
    {
        App::db()->run(
            'UPDATE ota_channels SET last_sync_at = NOW(), last_status = ? WHERE hotel_id = ? AND channel = ?',
            [$status, $hotelId, $channel]
        );
    }
}
