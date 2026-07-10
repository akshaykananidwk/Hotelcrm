<?php
namespace App\Core;

/** PSR-4-ish autoloader mapping the App\ namespace to /app. */
class Autoloader
{
    public static function register(string $baseDir): void
    {
        spl_autoload_register(function (string $class) use ($baseDir) {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }
}
