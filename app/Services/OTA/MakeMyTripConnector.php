<?php
namespace App\Services\OTA;

/**
 * MakeMyTrip / Goibibo (InGo-MMT) web-automation connector.
 *
 * MakeMyTrip's "Connect" extranet is the property portal hotels use to manage
 * inventory, rates and bookings. Small properties are frequently not granted
 * direct API access, so this connector drives the extranet UI instead.
 *
 * IMPORTANT — the default flow selectors below are TEMPLATE PLACEHOLDERS, not
 * verified live selectors. After logging into your own extranet, open browser
 * dev-tools, copy the real element selectors, and paste your corrected flow
 * JSON in Settings → OTA → MakeMyTrip → Automation. The engine is fully
 * config-driven, so no code changes are needed to adapt to portal updates.
 */
class MakeMyTripConnector extends WebAutomationConnector
{
    private string $channel;
    private string $name;
    private string $portalUrl;

    public function __construct(int $hotelId, array $credentials, string $channel = 'makemytrip')
    {
        parent::__construct($hotelId, $credentials);
        $this->channel = $channel;
        if ($channel === 'goibibo') {
            $this->name = 'Goibibo';
            $this->portalUrl = 'https://ingommt.goibibo.com/';
        } else {
            $this->name = 'MakeMyTrip';
            $this->portalUrl = 'https://ingommt.makemytrip.com/';
        }
    }

    public function channelKey(): string { return $this->channel; }
    public function displayName(): string { return $this->name; }

    protected function requiredCredentials(): array
    {
        return ['username', 'password'];
    }

    protected function defaultFlows(): array
    {
        $loginUrl = $this->automation()['login_url'] ?? $this->portalUrl;
        return [
            // ---- Login -------------------------------------------------
            'login' => [
                ['action' => 'navigate', 'url' => $loginUrl],
                ['action' => 'waitFor', 'selector' => 'input[type="text"], #username, input[name="username"]', 'timeout' => 15000],
                ['action' => 'type', 'selector' => 'input[name="username"], #username', 'value' => '{{username}}'],
                ['action' => 'type', 'selector' => 'input[type="password"], #password', 'value' => '{{password}}'],
                ['action' => 'click', 'selector' => 'button[type="submit"], #loginBtn'],
                ['action' => 'sleep', 'ms' => 3000],
                ['action' => 'screenshot', 'name' => 'mmt_after_login'],
                // Confirm login by checking the URL / a dashboard element:
                ['action' => 'assertUrlContains', 'value' => '{{post_login_url_contains}}'],
            ],
            // ---- Push inventory / availability -------------------------
            'push_inventory' => [
                ['action' => 'navigate', 'url' => '{{inventory_url}}'],
                ['action' => 'waitFor', 'selector' => '{{avail_field}}', 'timeout' => 15000],
                ['action' => 'type', 'selector' => '{{avail_field}}', 'value' => '{{avail}}'],
                ['action' => 'selectOption', 'selector' => '{{stop_sell_field}}', 'value' => '{{stop_sell}}'],
                ['action' => 'click', 'selector' => '{{inventory_save}}'],
                ['action' => 'sleep', 'ms' => 1500],
                ['action' => 'screenshot', 'name' => 'mmt_inventory'],
            ],
            // ---- Push rates --------------------------------------------
            'push_rates' => [
                ['action' => 'navigate', 'url' => '{{rates_url}}'],
                ['action' => 'waitFor', 'selector' => '{{rate_field}}', 'timeout' => 15000],
                ['action' => 'type', 'selector' => '{{rate_field}}', 'value' => '{{rate}}'],
                ['action' => 'click', 'selector' => '{{rate_save}}'],
                ['action' => 'sleep', 'ms' => 1500],
            ],
            // ---- Pull bookings -----------------------------------------
            'pull_bookings' => [
                ['action' => 'navigate', 'url' => '{{bookings_url}}'],
                ['action' => 'waitFor', 'selector' => '{{booking_row}}', 'timeout' => 15000],
                ['action' => 'extractAll', 'selector' => '{{booking_ref_cell}}', 'as' => 'booking_refs'],
                ['action' => 'extractAll', 'selector' => '{{booking_guest_cell}}', 'as' => 'booking_guests'],
                ['action' => 'screenshot', 'name' => 'mmt_bookings'],
            ],
        ];
    }
}
