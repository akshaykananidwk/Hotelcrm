<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Audit;
use App\Core\App;
use App\Models\Setting;
use App\Models\OtaChannel;
use App\Services\BackupService;

class SettingsController extends Controller
{
    private Setting $settings;

    /** Setting groups and which of their keys are secret. */
    private array $groups = [
        'smtp'      => ['title' => 'Email (SMTP)', 'secrets' => ['password']],
        'whatsapp'  => ['title' => 'WhatsApp', 'secrets' => ['session_id', 'api_key']],
        'payment'   => ['title' => 'Payment Gateways', 'secrets' => ['razorpay_secret', 'phonepe_salt', 'payu_salt']],
        'mikrotik'  => ['title' => 'MikroTik Wi-Fi', 'secrets' => ['password']],
        'ota'       => ['title' => 'OTA Credentials', 'secrets' => []],
        'company'   => ['title' => 'Company / Hotel', 'secrets' => []],
        'api'       => ['title' => 'API Keys', 'secrets' => []],
    ];

    public function __construct()
    {
        $this->settings = new Setting();
    }

    public function index(): void
    {
        $this->authorize('settings.manage');
        $this->redirect('/settings/smtp');
    }

    public function group($params): void
    {
        $this->authorize('settings.manage');
        $group = $params['group'];
        if (!isset($this->groups[$group])) {
            $this->redirect('/settings/smtp');
        }
        $hotelId = $this->currentHotelId();
        $data = [
            'title' => 'Settings · ' . $this->groups[$group]['title'],
            'group' => $group,
            'groups' => $this->groups,
            'values' => $this->settings->group($group, $hotelId),
        ];
        if ($group === 'ota') {
            $data['channels'] = (new OtaChannel())->forHotel($hotelId);
        }
        if ($group === 'api') {
            $data['tokens'] = App::db()->all(
                'SELECT t.*, u.name AS user_name FROM api_tokens t JOIN users u ON u.id = t.user_id ORDER BY t.id DESC'
            );
        }
        $this->view('settings/index', $data);
    }

    public function save($params): void
    {
        $this->authorize('settings.manage');
        $group = $params['group'];
        if (!isset($this->groups[$group])) {
            $this->redirect('/settings/smtp');
        }
        $hotelId = $this->currentHotelId();

        if ($group === 'ota') {
            $this->saveOta($hotelId);
        } elseif ($group === 'api') {
            $this->generateToken();
            return;
        } else {
            $input = (array) Request::input('settings', []);
            $this->settings->saveGroup($group, $input, $hotelId, $this->groups[$group]['secrets']);
        }
        Audit::log('settings.save', 'settings', $group);
        Session::flash('success', $this->groups[$group]['title'] . ' settings saved.');
        $this->redirect('/settings/' . $group);
    }

    private function saveOta(int $hotelId): void
    {
        $channelModel = new OtaChannel();
        foreach ((array) Request::input('channel', []) as $channelKey => $fields) {
            $existing = $channelModel->byChannel($hotelId, $channelKey);
            $creds = array_filter([
                'api_key' => $fields['api_key'] ?? '',
                'username' => $fields['username'] ?? '',
                'password' => $fields['password'] ?? '',
                'hotel_id' => $fields['hotel_id'] ?? '',
                'endpoint' => $fields['endpoint'] ?? '',
            ], fn ($v) => $v !== '');
            $data = [
                'credentials' => json_encode($creds),
                'is_enabled' => !empty($fields['enabled']) ? 1 : 0,
            ];
            if ($existing) {
                $channelModel->update($existing['id'], $data);
            } else {
                $channelModel->create(array_merge($data, [
                    'hotel_id' => $hotelId,
                    'channel' => $channelKey,
                    'display_name' => ucfirst(str_replace('_', ' ', $channelKey)),
                ]));
            }
        }
    }

    private function generateToken(): void
    {
        $plain = \App\Core\Security::token(24);
        App::db()->insert('api_tokens', [
            'user_id' => Auth::id(),
            'name' => Request::input('token_name', 'API Token'),
            'token_hash' => hash('sha256', $plain),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('api_token.create', 'api_token');
        // Show the plaintext token once.
        Session::flash('success', 'API token created. Copy it now, it will not be shown again: ' . $plain);
        $this->redirect('/settings/api');
    }

    /** Trigger a database backup. */
    public function backup(): void
    {
        $this->authorize('settings.manage');
        try {
            $file = (new BackupService())->create();
            Audit::log('backup.create', 'backup', null, ['file' => basename($file)]);
            Session::flash('success', 'Backup created: ' . basename($file));
        } catch (\Throwable $e) {
            Session::flash('error', 'Backup failed: ' . $e->getMessage());
        }
        $this->back();
    }
}
