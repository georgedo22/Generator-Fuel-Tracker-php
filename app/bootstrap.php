<?php
/**
 * Application bootstrap. Defines paths, loads config, registers the
 * autoloader, starts the session and sets up error handling.
 */

declare(strict_types=1);

define('APP_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    // Namespaced classes live under app/core, app/models, app/services, app/controllers.
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    // Map namespace segment (Core, Models, ...) to its lowercase directory.
    $parts[0] = strtolower($parts[0]);
    $file = APP_ROOT . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_ROOT . '/core/ui.php';

if (!function_exists('config')) {
    /**
     * Read a configuration value using dot notation, e.g. config('db.host').
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $config = require APP_ROOT . '/config/config.php';
        }
        $segments = explode('.', $key);
        $value = $config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return PROJECT_ROOT . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return APP_ROOT . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

// Basic error reporting. Never display raw errors in production.
$debug = (bool) config('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set((string) config('app.timezone', 'UTC'));

\App\Core\Session::start();