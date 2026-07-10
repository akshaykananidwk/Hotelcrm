<?php
/**
 * Global template & utility helpers. Autoloaded via bootstrap.php.
 */

use App\Core\Security;
use App\Core\App;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Session;

if (!function_exists('e')) {
    /** HTML-escape a value for safe output. */
    function e($value): string
    {
        return Security::e($value);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return App::baseUrl($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return App::baseUrl('public/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return Auth::can($permission);
    }
}

if (!function_exists('money')) {
    /** Format a monetary amount with a currency symbol. */
    function money($amount, string $symbol = '₹'): string
    {
        return $symbol . number_format((float) $amount, 2);
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return App::config($key, $default);
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = ''): string
    {
        $old = Session::get('_old', []);
        return e($old[$key] ?? $default);
    }
}

if (!function_exists('active_when')) {
    /** Return 'active' if the current URI starts with $prefix. */
    function active_when(string $prefix): string
    {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        return str_starts_with($uri, trim($prefix, '/')) ? 'active' : '';
    }
}

if (!function_exists('dt')) {
    /** Human-friendly datetime formatter. */
    function dt(?string $value, string $format = 'd M Y'): string
    {
        if (!$value) {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date($format, $ts) : e($value);
    }
}

if (!function_exists('badge')) {
    /** Map a status to a Bootstrap badge class. */
    function badge(?string $status): string
    {
        $status = (string) $status;
        $map = [
            'active' => 'success', 'inactive' => 'secondary', 'pending' => 'warning',
            'confirmed' => 'primary', 'checked_in' => 'info', 'checked_out' => 'secondary',
            'cancelled' => 'danger', 'no_show' => 'dark', 'paid' => 'success',
            'partial' => 'warning', 'unpaid' => 'danger', 'clean' => 'success',
            'dirty' => 'danger', 'inspected' => 'info', 'maintenance' => 'warning',
            'available' => 'success', 'occupied' => 'primary', 'blocked' => 'dark',
            'success' => 'success', 'failed' => 'danger', 'queued' => 'secondary',
        ];
        return $map[$status] ?? 'secondary';
    }
}
