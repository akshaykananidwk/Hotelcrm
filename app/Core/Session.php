<?php
namespace App\Core;

/** Session facade with flash-message support. */
class Session
{
    public static function start(array $cfg = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name($cfg['name'] ?? 'HOTELCRM_SESS');
        session_set_cookie_params([
            'lifetime' => $cfg['lifetime'] ?? 7200,
            'path'     => '/',
            'secure'   => $cfg['secure'] ?? false,
            'httponly' => $cfg['httponly'] ?? true,
            'samesite' => $cfg['samesite'] ?? 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /** Flash a one-time message (retrieved on the next request). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function getFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }
}
