<?php
namespace App\Services\Automation;

use App\Core\Logger;

/**
 * Minimal, dependency-free W3C WebDriver client. Speaks the standard WebDriver
 * JSON wire protocol over HTTP to a driver such as `chromedriver` (a single
 * native binary) or a Selenium hub — no Composer package, Node or Java in the
 * app itself.
 *
 * This is the "real browser" automation path: it launches a (headless) Chrome
 * through the driver and drives the actual OTA extranet UI — filling the login
 * form, clicking through pages and reading rendered content — for portals that
 * are too JS-heavy for the plain WebSession client.
 *
 * Typical server setup:
 *   chromedriver --port=9515        # keep running (systemd) on the app host
 * Config keys (per channel or global, from settings):
 *   driver_url  (default http://127.0.0.1:9515)
 *   browser_binary (path to chrome/chromium)
 *   headless (bool, default true)
 */
class BrowserDriver
{
    private string $driverUrl;
    private array $options;
    private ?string $sessionId = null;

    public function __construct(array $config = [])
    {
        $this->driverUrl = rtrim($config['driver_url'] ?? 'http://127.0.0.1:9515', '/');
        $this->options = $config;
    }

    /** Start a browser session. */
    public function start(): void
    {
        $args = ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--window-size=1440,900'];
        if (($this->options['headless'] ?? true)) {
            $args[] = '--headless=new';
        }
        $chromeOptions = ['args' => $args];
        if (!empty($this->options['browser_binary'])) {
            $chromeOptions['binary'] = $this->options['browser_binary'];
        }
        $caps = ['capabilities' => ['alwaysMatch' => [
            'browserName' => 'chrome',
            'goog:chromeOptions' => $chromeOptions,
            'acceptInsecureCerts' => (bool) ($this->options['accept_insecure'] ?? false),
        ]]];
        $res = $this->call('POST', '/session', $caps);
        $this->sessionId = $res['value']['sessionId'] ?? ($res['sessionId'] ?? null);
        if (!$this->sessionId) {
            throw new \RuntimeException('Could not create WebDriver session: ' . json_encode($res));
        }
        $this->setTimeouts();
    }

    private function setTimeouts(): void
    {
        $this->call('POST', "/session/{$this->sessionId}/timeouts", [
            'pageLoad' => (int) ($this->options['page_load_timeout'] ?? 30000),
            'script' => 20000,
        ]);
    }

    public function navigate(string $url): void
    {
        $this->call('POST', "/session/{$this->sessionId}/url", ['url' => $url]);
    }

    public function currentUrl(): string
    {
        return (string) ($this->call('GET', "/session/{$this->sessionId}/url")['value'] ?? '');
    }

    public function pageSource(): string
    {
        return (string) ($this->call('GET', "/session/{$this->sessionId}/source")['value'] ?? '');
    }

    public function title(): string
    {
        return (string) ($this->call('GET', "/session/{$this->sessionId}/title")['value'] ?? '');
    }

    /** Find a single element id by CSS selector, or null. */
    public function find(string $selector, string $using = 'css selector'): ?string
    {
        try {
            $res = $this->call('POST', "/session/{$this->sessionId}/element", ['using' => $using, 'value' => $selector]);
            $val = $res['value'] ?? [];
            return is_array($val) ? ($val[array_key_first($val)] ?? null) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Wait until a selector is present (polling), up to $timeoutMs. */
    public function waitFor(string $selector, int $timeoutMs = 15000, string $using = 'css selector'): ?string
    {
        $deadline = microtime(true) + $timeoutMs / 1000;
        do {
            $el = $this->find($selector, $using);
            if ($el !== null) {
                return $el;
            }
            usleep(250000);
        } while (microtime(true) < $deadline);
        return null;
    }

    public function type(string $selector, string $text, bool $clear = true): void
    {
        $el = $this->waitFor($selector) ?? throw new \RuntimeException("Element not found: $selector");
        if ($clear) {
            $this->call('POST', "/session/{$this->sessionId}/element/$el/clear", []);
        }
        $this->call('POST', "/session/{$this->sessionId}/element/$el/value", ['text' => $text]);
    }

    public function click(string $selector): void
    {
        $el = $this->waitFor($selector) ?? throw new \RuntimeException("Element not found: $selector");
        $this->call('POST', "/session/{$this->sessionId}/element/$el/click", []);
    }

    public function text(string $selector): ?string
    {
        $el = $this->find($selector);
        if ($el === null) {
            return null;
        }
        return (string) ($this->call('GET', "/session/{$this->sessionId}/element/$el/text")['value'] ?? '');
    }

    /** Run JavaScript in the page and return its result. */
    public function script(string $js, array $args = [])
    {
        return $this->call('POST', "/session/{$this->sessionId}/execute/sync", ['script' => $js, 'args' => $args])['value'] ?? null;
    }

    /** Base64 PNG screenshot (useful for verifying a login flow). */
    public function screenshot(): string
    {
        return (string) ($this->call('GET', "/session/{$this->sessionId}/screenshot")['value'] ?? '');
    }

    /** Save a screenshot to disk; returns the path. */
    public function saveScreenshot(string $path): string
    {
        file_put_contents($path, base64_decode($this->screenshot()));
        return $path;
    }

    public function cookies(): array
    {
        return $this->call('GET', "/session/{$this->sessionId}/cookie")['value'] ?? [];
    }

    public function quit(): void
    {
        if ($this->sessionId) {
            try {
                $this->call('DELETE', "/session/{$this->sessionId}");
            } catch (\Throwable $e) {
                Logger::warn('WebDriver quit failed: ' . $e->getMessage());
            }
            $this->sessionId = null;
        }
    }

    public function isStarted(): bool
    {
        return $this->sessionId !== null;
    }

    /** Low-level WebDriver JSON call. */
    private function call(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init($this->driverUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT => 60,
        ]);
        if ($body !== null) {
            // Cast to object so an empty body serialises as `{}` (a valid JSON
            // object the W3C protocol requires) rather than `[]`.
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode((object) $body));
        }
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException("WebDriver connection failed ({$this->driverUrl}): $err");
        }
        $decoded = json_decode((string) $raw, true);
        if ($code >= 400) {
            $msg = $decoded['value']['message'] ?? "HTTP $code";
            throw new \RuntimeException('WebDriver error: ' . $msg);
        }
        return is_array($decoded) ? $decoded : ['value' => null];
    }

    public function __destruct()
    {
        $this->quit();
    }
}
