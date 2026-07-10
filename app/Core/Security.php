<?php
namespace App\Core;

/** Assorted security helpers: hashing, escaping, sanitising, uploads. */
class Security
{
    public static function hash(string $password): string
    {
        return password_hash($password, App::config('security.password_algo', PASSWORD_DEFAULT));
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /** HTML-escape output (XSS protection). */
    public static function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Handle a secure file upload. Validates extension and size, generates a
     * random name and stores it under the uploads directory.
     *
     * @return string|null relative stored path on success
     */
    public static function upload(array $file, string $subdir = ''): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $maxKb   = App::config('security.upload_max_kb', 5120);
        $allowed = App::config('security.upload_allowed', ['jpg', 'jpeg', 'png', 'pdf']);

        if ($file['size'] > $maxKb * 1024) {
            throw new \RuntimeException('File exceeds maximum allowed size.');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('File type not allowed.');
        }

        // Verify real MIME type, do not trust the browser-supplied one.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $safeMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($mime, $safeMimes, true)) {
            throw new \RuntimeException('File content not permitted.');
        }

        $base = rtrim(App::config('paths.uploads'), '/');
        $dir  = $subdir ? $base . '/' . trim($subdir, '/') : $base;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = self::token(16) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            // Fallback for CLI / tests where move_uploaded_file is unavailable.
            if (!rename($file['tmp_name'], $dest)) {
                return null;
            }
        }
        return ($subdir ? trim($subdir, '/') . '/' : '') . $name;
    }
}
