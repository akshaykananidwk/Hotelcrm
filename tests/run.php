<?php
/**
 * Zero-dependency test runner for framework logic that does not require a DB.
 * Run: php tests/run.php
 *
 * For full integration tests, load database/schema.sql into a test MySQL DB
 * and point config/config.php at it.
 */

require __DIR__ . '/../bootstrap.php';

use App\Core\Validator;
use App\Core\Security;
use App\Core\Csrf;
use App\Services\PdfService;
use App\Services\BookingService;
use App\Services\Notification\NotificationManager;
use App\Services\Notification\WhatsAppChannel;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\CashGateway;

$pass = 0;
$fail = 0;
function check(bool $cond, string $msg): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  ✓ $msg\n";
    } else {
        $fail++;
        echo "  ✗ FAIL: $msg\n";
    }
}

echo "Validator\n";
$v = new Validator(['email' => 'bad', 'age' => '15'], ['email' => 'required|email', 'age' => 'int|min:18']);
check(!$v->passes(), 'rejects bad email + below-min integer');
check(count($v->flatErrors()) === 2, 'reports two errors');
check((new Validator(['email' => 'a@b.com', 'age' => '20'], ['email' => 'required|email', 'age' => 'int|min:18']))->passes(), 'accepts valid input');
check(!(new Validator(['name' => 'Jo'], ['name' => 'min:3']))->passes(), 'enforces string min length');

echo "Security\n";
$h = Security::hash('secret');
check(Security::verify('secret', $h), 'password hash + verify');
check(!Security::verify('wrong', $h), 'rejects wrong password');

echo "CSRF\n";
$_SESSION = [];
$t = Csrf::token();
check(Csrf::verify($t), 'verifies own token');
check(!Csrf::verify('nope'), 'rejects forged token');

echo "BookingService\n";
$bs = new BookingService();
check($bs->nights('2026-07-10', '2026-07-13') === 3, 'computes 3 nights');
check($bs->nights('2026-07-10', '2026-07-10') === 1, 'minimum 1 night');

echo "Notifications\n";
$nm = new NotificationManager();
$rm = new ReflectionMethod($nm, 'interpolate');
$rm->setAccessible(true);
check($rm->invoke($nm, 'Hi {{n}} {{c}}', ['n' => 'R', 'c' => 'X']) === 'Hi R X', 'template interpolation');
$wa = new WhatsAppChannel(['session_id' => 'x', 'api_key' => 'y']);
$rn = new ReflectionMethod($wa, 'normalizeNumber');
$rn->setAccessible(true);
check($rn->invoke($wa, '9812345678') === '919812345678', 'WhatsApp adds country code');
check($rn->invoke($wa, '+91 98123-45678') === '919812345678', 'WhatsApp strips formatting');
check((new WhatsAppChannel(['session_id' => '', 'api_key' => '']))->send('9812345678', '', 'hi')['success'] === false, 'fails without credentials');

echo "PDF\n";
$pdf = (new PdfService())->heading('T')->line('L')->rule();
$bytes = $pdf->output();
check(str_starts_with($bytes, '%PDF-1.4'), 'valid PDF header');
check(str_contains($bytes, '%%EOF'), 'valid PDF trailer');

echo "Payments\n";
check(count((new PaymentManager())->available()) >= 4, 'lists gateways');
$o = (new CashGateway())->createOrder(500, 'INR');
check($o['success'] && !empty($o['reference']), 'cash gateway creates order');

echo "Crypto (credential encryption)\n";
$blob = App\Core\Crypto::encryptArray(['username' => 'u', 'password' => 'p@ss']);
check(App\Core\Crypto::isEncrypted($blob), 'produces enc:v1 ciphertext');
check(!str_contains($blob, 'p@ss'), 'plaintext not exposed in ciphertext');
check((App\Core\Crypto::decryptArray($blob)['password'] ?? '') === 'p@ss', 'round-trips correctly');
check(App\Core\Crypto::decrypt('plain-legacy') === 'plain-legacy', 'tolerates legacy plaintext');

echo "Web automation (HTML parsing + flow interpolation)\n";
$ws = new App\Services\Automation\WebSession();
$html = '<form id="loginForm" action="/login"><input type="hidden" name="csrf" value="tok123"></form>';
check(($ws->hiddenInputs($html)['csrf'] ?? '') === 'tok123', 'extracts hidden CSRF token');
check($ws->formAction($html, 'loginForm') === '/login', 'extracts form action');
$flow = new App\Services\Automation\AutomationFlow(new App\Services\Automation\BrowserDriver(), ['username' => 'hoteluser']);
$ri = new ReflectionMethod($flow, 'interp');
$ri->setAccessible(true);
check($ri->invoke($flow, 'user={{username}}') === 'user=hoteluser', 'flow interpolates context vars');
$mmt = new App\Services\OTA\MakeMyTripConnector(1, [], 'makemytrip');
check($mmt->displayName() === 'MakeMyTrip', 'MakeMyTrip connector resolves');
check($mmt->testConnection()['success'] === false, 'guards when extranet credentials absent');

echo "\n" . str_repeat('=', 40) . "\n";
echo "PASSED: $pass   FAILED: $fail\n";
exit($fail === 0 ? 0 : 1);
