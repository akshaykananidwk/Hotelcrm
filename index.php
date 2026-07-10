<?php
/**
 * Front controller — the single entry point for the application.
 * All requests are routed through here (see .htaccess for rewrite rules).
 */

use App\Core\Router;
use App\Core\Csrf;
use App\Core\Request;

require __DIR__ . '/bootstrap.php';

// Verify CSRF for state-changing browser requests (API uses bearer tokens).
if (!str_starts_with(trim(parse_url(Request::uri(), PHP_URL_PATH), '/'), 'api/')) {
    Csrf::check();
}

$router = new Router();
require __DIR__ . '/routes.php';

$router->dispatch(Request::method(), Request::uri());
