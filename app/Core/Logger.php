<?php
namespace App\Core;

/** File-based logger plus global error/exception handlers. */
class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $dir = App::config('paths.logs', __DIR__ . '/../../storage/logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context) : ''
        );
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $m, array $c = []): void { self::log('error', $m, $c); }
    public static function info(string $m, array $c = []): void  { self::log('info', $m, $c); }
    public static function warn(string $m, array $c = []): void  { self::log('warning', $m, $c); }

    public static function handleError($severity, $message, $file, $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        self::error("$message in $file:$line", ['severity' => $severity]);
        return true;
    }

    public static function handleException(\Throwable $e): void
    {
        self::error($e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        http_response_code(500);
        $debug = App::config('app.debug', false);
        if (Request::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error'   => 'Internal server error',
                'message' => $debug ? $e->getMessage() : null,
            ]);
            return;
        }
        if ($debug) {
            echo '<h1>Unhandled Exception</h1><pre>' . Security::e($e) . '</pre>';
        } else {
            echo '<h1>500 — Something went wrong</h1><p>The error has been logged.</p>';
        }
    }

    public static function handleShutdown(): void
    {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::error("Fatal: {$err['message']} in {$err['file']}:{$err['line']}");
        }
    }
}
