<?php
namespace App\Services\Automation;

use App\Core\Logger;

/**
 * Pure-PHP HTTP session client (cURL + persistent cookie jar). This is the
 * lightweight automation path: it logs into an OTA extranet by replaying the
 * login form POST and then reuses the authenticated session cookies to call
 * the same internal endpoints the browser UI uses. No browser required, so it
 * runs cheaply inside the background worker.
 *
 * Use this when the target portal's login is a plain HTML form (no heavy JS or
 * CAPTCHA). For JS-driven or bot-protected portals, use BrowserDriver instead.
 */
class WebSession
{
    private string $cookieFile;
    private string $userAgent;
    private array $lastHeaders = [];
    private int $lastStatus = 0;
    private ?string $lastUrl = null;

    public function __construct(?string $cookieFile = null, ?string $userAgent = null)
    {
        $this->cookieFile = $cookieFile ?: tempnam(sys_get_temp_dir(), 'wsess_');
        $this->userAgent = $userAgent
            ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36';
    }

    public function get(string $url, array $headers = []): string
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function post(string $url, array $data, array $headers = []): string
    {
        $body = is_array($data) ? http_build_query($data) : $data;
        return $this->request('POST', $url, $body, $headers);
    }

    public function postJson(string $url, array $data, array $headers = []): string
    {
        return $this->request('POST', $url, json_encode($data),
            array_merge(['Content-Type: application/json'], $headers));
    }

    private function request(string $method, string $url, ?string $body, array $headers): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 6,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($c, $line) {
            $this->lastHeaders[] = trim($line);
            return strlen($line);
        });
        $this->lastHeaders = [];
        $response = curl_exec($ch);
        $this->lastStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            Logger::warn('WebSession request failed', ['url' => $url, 'error' => $err]);
            throw new \RuntimeException("HTTP request failed: $err");
        }
        return (string) $response;
    }

    public function status(): int { return $this->lastStatus; }
    public function currentUrl(): ?string { return $this->lastUrl; }

    /**
     * Extract all hidden input fields (name => value) from an HTML form — handy
     * for grabbing CSRF tokens / view-state before submitting a login form.
     */
    public function hiddenInputs(string $html): array
    {
        $out = [];
        if (preg_match_all('/<input[^>]+type=["\']hidden["\'][^>]*>/i', $html, $tags)) {
            foreach ($tags[0] as $tag) {
                if (preg_match('/name=["\']([^"\']+)["\']/i', $tag, $n)
                    && preg_match('/value=["\']([^"\']*)["\']/i', $tag, $v)) {
                    $out[$n[1]] = html_entity_decode($v[1], ENT_QUOTES);
                }
            }
        }
        return $out;
    }

    /** Extract the action URL of the first (or named) form on the page. */
    public function formAction(string $html, ?string $formId = null): ?string
    {
        $pattern = $formId
            ? '/<form[^>]*id=["\']' . preg_quote($formId, '/') . '["\'][^>]*action=["\']([^"\']+)["\']/i'
            : '/<form[^>]*action=["\']([^"\']+)["\']/i';
        return preg_match($pattern, $html, $m) ? html_entity_decode($m[1], ENT_QUOTES) : null;
    }

    /** Grab a single value via regex (e.g. a token embedded in JS). */
    public function match(string $html, string $pattern): ?string
    {
        return preg_match($pattern, $html, $m) ? $m[1] : null;
    }

    public function cookieFile(): string { return $this->cookieFile; }

    public function __destruct()
    {
        if (str_starts_with(basename($this->cookieFile), 'wsess_') && is_file($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }
}
