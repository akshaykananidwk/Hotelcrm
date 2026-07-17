<?php
/**
 * Live browser-automation test. Exercises BrowserDriver + AutomationFlow +
 * MakeMyTripConnector against a REAL headless Chromium, using a bundled mock
 * "extranet" so it needs no external site.
 *
 * Requirements (auto-detected; the test SKIPS cleanly if missing):
 *   - a running WebDriver (chromedriver) at CHROMEDRIVER_URL (default :9515)
 *   - a Chrome/Chromium binary at CHROMIUM_BIN
 *
 * Run:
 *   chromedriver --port=9515 &
 *   CHROMIUM_BIN=/path/to/chromium php tests/automation_live.php
 */

require __DIR__ . '/../bootstrap.php';

use App\Services\Automation\BrowserDriver;
use App\Services\Automation\AutomationFlow;

$driverUrl = getenv('CHROMEDRIVER_URL') ?: 'http://127.0.0.1:9515';
$chromium  = getenv('CHROMIUM_BIN') ?: '/opt/pw-browsers/chromium';

// Probe the driver.
$ch = curl_init($driverUrl . '/status');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
$up = curl_exec($ch) !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
curl_close($ch);
if (!$up || !is_file($chromium)) {
    echo "SKIP: WebDriver at $driverUrl or Chromium at $chromium not available.\n";
    echo "      Start `chromedriver --port=9515` and set CHROMIUM_BIN to run this test.\n";
    exit(0);
}

// Start a bundled mock extranet on a random port.
$port = random_int(8100, 8999);
$mock = __DIR__ . '/fixtures/mock_extranet.php';
$proc = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$port", $mock],
    [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes
);
usleep(700000);
$base = "http://127.0.0.1:$port";

$pass = 0; $fail = 0;
$check = function ($c, $m) use (&$pass, &$fail) {
    echo ($c ? "  ✓ " : "  ✗ FAIL: ") . $m . "\n";
    $c ? $pass++ : $fail++;
};

try {
    $driver = new BrowserDriver(['driver_url' => $driverUrl, 'browser_binary' => $chromium, 'headless' => true]);
    $flow = new AutomationFlow($driver, [
        'login_url' => "$base/login", 'username' => 'hoteluser', 'password' => 'secret123',
    ]);
    $r = $flow->run([
        ['action' => 'navigate', 'url' => '{{login_url}}'],
        ['action' => 'type', 'selector' => '#username', 'value' => '{{username}}'],
        ['action' => 'type', 'selector' => '#password', 'value' => '{{password}}'],
        ['action' => 'click', 'selector' => '#loginBtn'],
        ['action' => 'waitFor', 'selector' => '#welcome', 'timeout' => 10000],
        ['action' => 'assertUrlContains', 'value' => 'dashboard'],
        ['action' => 'extractAll', 'selector' => '#bookings .ref', 'as' => 'refs'],
    ]);
    $check($r['success'] === true, 'headless login flow completed');
    $check(in_array('MMT-1001', $r['results']['refs'] ?? [], true), 'scraped bookings from rendered page');
} finally {
    if (isset($proc) && is_resource($proc)) {
        proc_terminate($proc);
        proc_close($proc);
    }
}

echo "\nPASSED=$pass FAILED=$fail\n";
exit($fail === 0 ? 0 : 1);
