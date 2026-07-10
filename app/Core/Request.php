<?php
namespace App\Core;

/** Wrapper around the current HTTP request. */
class Request
{
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /** Merged input (JSON body for API requests + form fields). */
    public static function input(?string $key = null, $default = null)
    {
        static $data = null;
        if ($data === null) {
            $data = $_POST;
            $raw = file_get_contents('php://input');
            if ($raw && str_contains(self::header('Content-Type') ?? '', 'application/json')) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $data = array_merge($data, $json);
                }
            }
        }
        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    public static function all(): array
    {
        return self::input();
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public static function bearerToken(): ?string
    {
        $header = self::header('Authorization') ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            return trim($m[1]);
        }
        return $_GET['api_token'] ?? null;
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public static function wantsJson(): bool
    {
        $accept = self::header('Accept') ?? '';
        return str_contains($accept, 'application/json')
            || str_starts_with(trim(self::uri(), '/'), 'api/');
    }
}
