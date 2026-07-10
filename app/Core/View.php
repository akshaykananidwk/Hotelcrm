<?php
namespace App\Core;

/** Simple PHP-template view renderer with layout support. */
class View
{
    private static string $viewPath = __DIR__ . '/../Views/';

    /** Named content sections (e.g. page-specific scripts) shared to layouts. */
    private static array $sections = [];

    public static function startSection(string $name): void
    {
        self::$sections[$name] = self::$sections[$name] ?? '';
        ob_start();
    }

    public static function endSection(string $name): void
    {
        self::$sections[$name] .= ob_get_clean();
    }

    public static function section(string $name): string
    {
        return self::$sections[$name] ?? '';
    }

    /**
     * Render a view, optionally wrapped in a layout.
     *
     * @param string      $view   dot or slash path, e.g. 'rooms/index'
     * @param array       $data   variables exposed to the template
     * @param string|null $layout layout name under Views/layouts, or null
     */
    public static function render(string $view, array $data = [], ?string $layout = 'app'): string
    {
        $content = self::renderPartial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::renderPartial('layouts/' . $layout, array_merge($data, ['content' => $content]));
    }

    public static function renderPartial(string $view, array $data = []): string
    {
        $file = self::$viewPath . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /** Convenience escape helper usable inside templates as e(). */
    public static function e($value): string
    {
        return Security::e($value);
    }
}
