<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\App;
use App\Core\Logger;

/**
 * Receives inbound OTA webhooks (new/modified/cancelled bookings). Each payload
 * is logged as a sync job for asynchronous, retry-safe processing so a slow or
 * failing import never blocks the OTA's delivery.
 */
class OtaWebhookController extends Controller
{
    public function handle($params): void
    {
        header('Content-Type: application/json');
        $channel = $params['channel'];
        $payload = Request::all();

        // Signature verification hook — each OTA has its own scheme; verify here
        // once the partner shares the signing secret.
        Logger::info('OTA webhook received', ['channel' => $channel]);

        $hotelId = (int) (Request::get('hotel_id') ?: App::db()->scalar('SELECT id FROM hotels ORDER BY id LIMIT 1'));
        App::db()->insert('sync_jobs', [
            'hotel_id' => $hotelId,
            'channel' => $channel,
            'job_type' => 'bookings',
            'payload' => json_encode($payload),
            'status' => 'queued',
            'run_after' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true, 'message' => 'Webhook accepted for processing.']);
    }
}
