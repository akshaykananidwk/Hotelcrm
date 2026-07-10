<?php
/**
 * Background queue worker. Processes OTA sync jobs with retry/backoff.
 * Schedule via cron:  * * * * * php /path/to/cli/worker.php >> storage/logs/worker.log 2>&1
 */

require __DIR__ . '/../bootstrap.php';

use App\Services\OTA\ChannelManager;

$start = microtime(true);
$processed = (new ChannelManager())->processQueue(50);
printf("[%s] Processed %d sync job(s) in %.2fs\n", date('Y-m-d H:i:s'), $processed, microtime(true) - $start);
