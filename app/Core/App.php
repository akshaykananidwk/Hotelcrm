<?php
namespace App\Core;

/**
 * Application kernel: holds global configuration, registers the autoloader,
 * boots services (session, error handling) and hands control to the Router.
 */
class App
{
    private static array $config = [];
    private static bool $booted = false;

    /** Boot the framework with a resolved configuration array. */
    public static function boot(array $config): void
    {
        if (self::$booted) {
            return;
        }
        self::$config = $config;

        date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

        // Error handling / logging.
        $debug = $config['app']['debug'] ?? false;
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        set_error_handler([Logger::class, 'handleError']);
        set_exception_handler([Logger::class, 'handleException']);
        register_shutdown_function([Logger::class, 'handleShutdown']);

        Session::start($config['session'] ?? []);

        self::$booted = true;
    }

    /** Dot-notation config accessor: App::config('db.host'). */
    public static function config(?string $key = null, $default = null)
    {
        if ($key === null) {
            return self::$config;
        }
        $parts = explode('.', $key);
        $value = self::$config;
        foreach ($parts as $p) {
            if (!is_array($value) || !array_key_exists($p, $value)) {
                return $default;
            }
            $value = $value[$p];
        }
        return $value;
    }

    public static function db(): Database
    {
        return Database::instance(self::$config['db']);
    }

    public static function baseUrl(string $path = ''): string
    {
        $base = rtrim(self::$config['app']['url'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}
