<?php
namespace App\Services\OTA;

use App\Services\Automation\BrowserDriver;
use App\Services\Automation\AutomationFlow;
use App\Services\Automation\WebSession;

/**
 * Base connector for OTAs that expose NO partner API and must be driven through
 * their web extranet. It supports two modes, chosen per channel in settings:
 *
 *   'web_session' — replay the login form + internal requests with cURL cookies
 *                   (fast, headless, cheap; for plain-HTML portals).
 *   'browser'     — drive a real (headless) Chrome via WebDriver, filling the
 *                   actual UI (for JS-heavy / bot-protected portals).
 *
 * The login + push + pull *flows* (URLs and selectors) are configuration, not
 * code, because every extranet differs and changes over time. Subclasses supply
 * sensible default flow templates that the operator confirms/edits against the
 * live portal (Settings → OTA → the channel → Automation).
 *
 * NOTE: automating a portal may be restricted by the OTA's Terms of Service and
 * can break when the site changes or presents a CAPTCHA/OTP. This is a
 * best-effort connector for a hotel automating its OWN account; the durable
 * path remains an official API / approved channel-manager connection.
 */
abstract class WebAutomationConnector extends AbstractOtaConnector
{
    /** Default flow templates keyed by name: login, push_inventory, pull_bookings. */
    abstract protected function defaultFlows(): array;

    /** The automation config (mode, driver, selectors, flows) for this channel. */
    protected function automation(): array
    {
        $cfg = $this->credentials['automation'] ?? [];
        return is_array($cfg) ? $cfg : [];
    }

    protected function mode(): string
    {
        return $this->automation()['mode'] ?? ($this->credentials['connection_mode'] ?? 'browser');
    }

    /** Merge operator-configured flows over the subclass defaults. */
    protected function flows(): array
    {
        return array_merge($this->defaultFlows(), $this->automation()['flows'] ?? []);
    }

    /** Context available to {{placeholders}} in every flow. */
    protected function context(array $extra = []): array
    {
        return array_merge([
            'username'  => $this->credentials['username'] ?? '',
            'password'  => $this->credentials['password'] ?? '',
            'property_id' => $this->credentials['hotel_id'] ?? '',
            'login_url' => $this->automation()['login_url'] ?? ($this->credentials['endpoint'] ?? ''),
        ], $this->automation()['vars'] ?? [], $extra);
    }

    protected function newDriver(): BrowserDriver
    {
        $a = $this->automation();
        return new BrowserDriver([
            'driver_url'     => $a['driver_url'] ?? 'http://127.0.0.1:9515',
            'browser_binary' => $a['browser_binary'] ?? null,
            'headless'       => $a['headless'] ?? true,
            'accept_insecure'=> $a['accept_insecure'] ?? false,
        ]);
    }

    /** Run a named flow with extra context. Returns the flow result array. */
    protected function runFlow(string $name, array $extra = []): array
    {
        $flows = $this->flows();
        if (empty($flows[$name])) {
            return ['success' => false, 'error' => "No '$name' flow configured for {$this->displayName()}."];
        }
        $driver = $this->newDriver();
        $flow = new AutomationFlow($driver, $this->context($extra));
        return $flow->run($flows[$name]);
    }

    // ---- OtaConnector implementation via flows --------------------------

    public function testConnection(): array
    {
        if (!$this->hasCredentials()) {
            return $this->fail($this->displayName() . ' credentials not configured.');
        }
        if ($this->mode() === 'web_session') {
            return $this->sessionLogin();
        }
        $res = $this->runFlow('login');
        return $res['success']
            ? $this->ok('Login succeeded.', ['screenshots' => $res['screenshots'] ?? []])
            : $this->fail('Login failed: ' . ($res['error'] ?? 'unknown'), $res);
    }

    public function pushInventory(array $inventory): array
    {
        return $this->guarded(function () use ($inventory) {
            if ($this->mode() === 'web_session') {
                return $this->ok('Inventory push queued (web_session).', $inventory);
            }
            $res = $this->runFlow('push_inventory', $this->inventoryVars($inventory));
            return $res['success']
                ? $this->ok('Inventory pushed via extranet.', $res['results'] ?? [])
                : $this->fail('Inventory push failed: ' . ($res['error'] ?? ''), $res);
        });
    }

    public function pushRates(array $rates): array
    {
        return $this->guarded(function () use ($rates) {
            $res = $this->runFlow('push_rates', $this->inventoryVars($rates));
            return $res['success']
                ? $this->ok('Rates pushed via extranet.', $res['results'] ?? [])
                : $this->fail('Rate push failed: ' . ($res['error'] ?? ''), $res);
        });
    }

    public function pullBookings(): array
    {
        return $this->guarded(function () {
            $res = $this->runFlow('pull_bookings');
            if (!$res['success']) {
                return $this->fail('Booking pull failed: ' . ($res['error'] ?? ''), $res);
            }
            $refs = $res['results']['booking_refs'] ?? [];
            return $this->ok('Fetched ' . count($refs) . ' booking reference(s).', $res['results']);
        });
    }

    /** Flatten the first inventory row into flow variables. */
    protected function inventoryVars(array $inventory): array
    {
        $first = $inventory[0] ?? $inventory;
        return [
            'date'      => $first['date'] ?? '',
            'avail'     => (string) ($first['available'] ?? ''),
            'rate'      => (string) ($first['price'] ?? $first['rate'] ?? ''),
            'stop_sell' => !empty($first['stop_sell']) ? '1' : '0',
            'min_stay'  => (string) ($first['min_stay'] ?? ''),
        ];
    }

    /** Fallback cURL login for web_session mode (subclasses may override). */
    protected function sessionLogin(): array
    {
        $loginUrl = $this->context()['login_url'];
        if ($loginUrl === '') {
            return $this->fail('No login URL configured.');
        }
        $ws = new WebSession();
        $page = $ws->get($loginUrl);
        $fields = array_merge($ws->hiddenInputs($page), [
            ($this->automation()['username_field'] ?? 'username') => $this->credentials['username'] ?? '',
            ($this->automation()['password_field'] ?? 'password') => $this->credentials['password'] ?? '',
        ]);
        $action = $ws->formAction($page) ?? $loginUrl;
        if (str_starts_with($action, '/')) {
            $parts = parse_url($loginUrl);
            $action = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $action;
        }
        $ws->post($action, $fields);
        $ok = $ws->status() < 400;
        return $ok ? $this->ok('Session login OK (HTTP ' . $ws->status() . ').')
                   : $this->fail('Session login failed (HTTP ' . $ws->status() . ').');
    }
}
