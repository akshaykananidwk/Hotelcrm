<?php
/**
 * Framework bootstrap. Registers the autoloader, loads configuration and
 * helpers, and boots the application kernel. Returns the resolved config.
 */

use App\Core\Autoloader;
use App\Core\App;

define('BASE_PATH', __DIR__);

require __DIR__ . '/app/Core/Autoloader.php';
Autoloader::register(__DIR__ . '/app');

// Load configuration (fall back to sample so the installer can run).
$configFile = __DIR__ . '/config/config.php';
if (!is_file($configFile)) {
    // Not installed yet — the /install wizard handles first-run setup.
    if (!str_contains($_SERVER['REQUEST_URI'] ?? '', 'install')) {
        header('Location: /install/');
        exit;
    }
    $config = require __DIR__ . '/config/config.sample.php';
} else {
    $config = require $configFile;
}

require __DIR__ . '/app/Helpers/helpers.php';

App::boot($config);

return $config;
