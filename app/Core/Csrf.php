<?php
namespace App\Core;

/** CSRF token generation & verification (double-submit synchroniser token). */
class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /** Hidden input helper for forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    /** Verify the incoming request token, aborting with 419 on failure. */
    public static function check(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
            if (!self::verify($token)) {
                http_response_code(419);
                if (Request::wantsJson()) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'CSRF token mismatch']);
                } else {
                    echo 'CSRF token mismatch. Please refresh and try again.';
                }
                exit;
            }
        }
    }
}
