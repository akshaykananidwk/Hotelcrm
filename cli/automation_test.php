<?php
/**
 * Automation self-test / flow debugger.
 *
 * Runs a channel's configured login flow against the live extranet and reports
 * the result, capturing a screenshot so you can verify your selectors before
 * enabling automated sync.
 *
 * Usage:
 *   php cli/automation_test.php <hotel_id> <channel> [flow]
 * Examples:
 *   php cli/automation_test.php 1 makemytrip            # runs the 'login' flow
 *   php cli/automation_test.php 1 makemytrip pull_bookings
 */

require __DIR__ . '/../bootstrap.php';

use App\Services\OTA\ChannelManager;

$hotelId = (int) ($argv[1] ?? 0);
$channel = $argv[2] ?? '';
$flow    = $argv[3] ?? 'login';

if (!$hotelId || !$channel) {
    fwrite(STDERR, "Usage: php cli/automation_test.php <hotel_id> <channel> [flow]\n");
    exit(1);
}

$connector = (new ChannelManager())->connector($hotelId, $channel);
echo "Testing {$connector->displayName()} ({$channel}) — flow: {$flow}\n";

$result = match ($flow) {
    'login'        => $connector->testConnection(),
    'pull_bookings'=> $connector->pullBookings(),
    'push_inventory' => $connector->pushInventory([['available' => 5, 'rate' => 3000]]),
    default        => $connector->testConnection(),
};

echo ($result['success'] ? "✓ SUCCESS" : "✗ FAILED") . " — " . ($result['message'] ?? '') . "\n";
if (!empty($result['data']['screenshots'])) {
    foreach ($result['data']['screenshots'] as $s) {
        echo "  screenshot: $s\n";
    }
}
if (!$result['success'] && !empty($result['data']['error_screenshot'])) {
    echo "  error screenshot: {$result['data']['error_screenshot']}\n";
}
exit($result['success'] ? 0 : 1);
