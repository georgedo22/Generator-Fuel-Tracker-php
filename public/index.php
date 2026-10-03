<?php

declare(strict_types=1);

/**
 * Front controller. All requests are routed through here.
 */

require __DIR__ . '/../app/bootstrap.php';

// When running under PHP's built-in server, let existing static files be
// served directly (Apache/Nginx handle this via rewrite rules).
if (PHP_SAPI === 'cli-server') {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $requested;
    if ($requested !== '/' && is_file($file)) {
        return false;
    }
}

use App\Core\Router;
use App\Core\Auth;

// Determine the base URL (supports installation in a subdirectory).
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
// When the document root is the project root and requests are rewritten
// into /public, strip that segment so generated URLs stay clean.
if (substr($scriptDir, -7) === '/public') {
    $scriptDir = substr($scriptDir, 0, -7);
}
if ($scriptDir === '.' || $scriptDir === '/') {
    $scriptDir = '';
}
define('APP_BASE', $scriptDir);

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return APP_BASE . ($path !== '' ? '/' . ltrim($path, '/') : '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return APP_BASE . '/assets/' . ltrim($path, '/');
    }
}

$router = new Router();

// ---------------------------------------------------------------
//  Root
// ---------------------------------------------------------------
$router->get('/', function () {
    \App\Core\Response::redirect(Auth::check() ? url('/dashboard') : url('/login'));
});

// ---------------------------------------------------------------
//  Authentication
// ---------------------------------------------------------------
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [\App\Controllers\AuthController::class, 'login']);
$router->post('/logout', [\App\Controllers\AuthController::class, 'logout']);

// ---------------------------------------------------------------
//  Dashboard
// ---------------------------------------------------------------
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index']);

// ---------------------------------------------------------------
//  Generators
// ---------------------------------------------------------------
$router->get('/generators', [\App\Controllers\GeneratorController::class, 'index']);
$router->get('/generators/export', [\App\Controllers\GeneratorController::class, 'export']);
$router->post('/generators/import', [\App\Controllers\GeneratorController::class, 'import']);
$router->post('/generators', [\App\Controllers\GeneratorController::class, 'store']);
$router->get('/generators/{id}', [\App\Controllers\GeneratorController::class, 'show']);
$router->post('/generators/{id}/update', [\App\Controllers\GeneratorController::class, 'update']);
$router->post('/generators/{id}/delete', [\App\Controllers\GeneratorController::class, 'destroy']);
$router->post('/generators/{id}/recompute', [\App\Controllers\GeneratorController::class, 'recompute']);

// ---------------------------------------------------------------
//  Fuel logs
// ---------------------------------------------------------------
$router->get('/logs', [\App\Controllers\LogController::class, 'index']);
$router->get('/logs/export', [\App\Controllers\LogController::class, 'export']);
$router->post('/logs', [\App\Controllers\LogController::class, 'store']);
$router->post('/logs/{id}/update', [\App\Controllers\LogController::class, 'update']);
$router->post('/logs/{id}/delete', [\App\Controllers\LogController::class, 'destroy']);

// ---------------------------------------------------------------
//  Reports
// ---------------------------------------------------------------
$router->get('/reports', [\App\Controllers\ReportController::class, 'index']);
$router->get('/reports/export', [\App\Controllers\ReportController::class, 'export']);

// ---------------------------------------------------------------
//  CSV import (fuel logs)
// ---------------------------------------------------------------
$router->get('/import', [\App\Controllers\ImportController::class, 'index']);
$router->post('/import', [\App\Controllers\ImportController::class, 'handle']);

// ---------------------------------------------------------------
//  Users (admin only)
// ---------------------------------------------------------------
$router->get('/users', [\App\Controllers\UserController::class, 'index']);
$router->post('/users', [\App\Controllers\UserController::class, 'store']);
$router->post('/users/{id}/update', [\App\Controllers\UserController::class, 'update']);
$router->post('/users/{id}/delete', [\App\Controllers\UserController::class, 'destroy']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');