<?php
namespace App\Core;

/** Simple file-based fixed-window rate limiter. */
class RateLimiter
{
    public static function allow(string $key, ?int $max = null, int $window = 60): bool
    {
        $max = $max ?? App::config('security.rate_limit', 60);
        $dir = App::config('paths.cache', __DIR__ . '/../../storage/cache');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/rl_' . md5($key) . '.json';
        $now = time();
        $data = ['count' => 0, 'reset' => $now + $window];
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded) && $decoded['reset'] > $now) {
                $data = $decoded;
            }
        }
        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return $data['count'] <= $max;
    }

    public static function reset(string $key): void
    {
        $dir = App::config('paths.cache');
        $file = $dir . '/rl_' . md5($key) . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
