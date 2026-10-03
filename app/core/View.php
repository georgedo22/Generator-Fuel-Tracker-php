<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny view renderer.
 */
final class View
{
    /**
     * Render a view inside a layout.
     *
     * @param string $view  e.g. "generators/index"
     * @param array  $data  variables extracted for the view
     * @param string $layout e.g. "layouts/app"
     */
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $viewFile = app_path('views/' . $view . '.php');
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        $content = (static function () use ($viewFile, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            require $viewFile;
            return (string) ob_get_clean();
        })();

        $layoutFile = app_path('views/' . $layout . '.php');
        if ($layout === '' || !is_file($layoutFile)) {
            return $content;
        }

        return (static function () use ($layoutFile, $data, $content): string {
            extract($data, EXTR_SKIP);
            ob_start();
            require $layoutFile;
            return (string) ob_get_clean();
        })();
    }
}