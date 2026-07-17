<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Audit;
use App\Core\App;
use App\Models\OtaChannel;
use App\Services\OTA\ChannelManager;

class ChannelController extends Controller
{
    public function index(): void
    {
        $this->authorize('channels.manage');
        $hotelId = $this->currentHotelId();
        $this->view('channels/index', [
            'title' => 'Channel Manager',
            'channels' => (new OtaChannel())->forHotel($hotelId),
            'recentJobs' => App::db()->all(
                'SELECT * FROM sync_jobs WHERE hotel_id = ? ORDER BY id DESC LIMIT 15', [$hotelId]
            ),
        ]);
    }

    /** Trigger a manual (queued) full sync for a channel. */
    public function sync($params): void
    {
        $this->authorize('channels.manage');
        $hotelId = $this->currentHotelId();
        $channel = (new OtaChannel())->find($params['id']);
        if (!$channel) {
            Session::flash('error', 'Channel not found.');
            $this->back();
            return;
        }
        (new ChannelManager())->enqueueFullSync($hotelId, $channel['channel']);
        Audit::log('channel.sync', 'ota_channel', $channel['id']);
        Session::flash('success', $channel['display_name'] . ' sync queued.');
        $this->back();
    }

    /**
     * Run a live login/connection test for a channel (API creds, cURL session
     * or a real browser flow depending on its connection mode). Reports the
     * result and any captured screenshot for verifying the automation flow.
     */
    public function test($params): void
    {
        $this->authorize('channels.manage');
        $hotelId = $this->currentHotelId();
        $channel = (new OtaChannel())->find($params['id']);
        if (!$channel) {
            Session::flash('error', 'Channel not found.');
            $this->back();
            return;
        }
        try {
            $result = (new ChannelManager())->connector($hotelId, $channel['channel'])->testConnection();
        } catch (\Throwable $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
        Audit::log('channel.test', 'ota_channel', $channel['id'], ['ok' => $result['success']]);

        $shots = $result['data']['screenshots'] ?? [];
        $shotNote = $shots ? ' (screenshot saved: ' . basename((string) $shots[0]) . ')' : '';
        Session::flash($result['success'] ? 'success' : 'error',
            $channel['display_name'] . ' test: ' . ($result['message'] ?? '') . $shotNote);
        $this->back();
    }
}
