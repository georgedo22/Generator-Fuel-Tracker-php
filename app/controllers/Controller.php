<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Shared controller behaviour.
 */
abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $user = Auth::user();
        $data['auth'] = $user;
        $data['flash'] = Flash::pull();
        $data['currentPath'] = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $data['csrf'] = \App\Core\Csrf::token();
        echo View::render($view, $data, $layout);
    }

    protected function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    protected function query(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    /** Keep submitted values to repopulate a form after a validation error. */
    protected function withOld(array $extra = []): void
    {
        $old = $_POST;
        unset($old['_csrf']);
        Session::set('_old', $old + $extra);
    }

    protected function old(): array
    {
        $old = Session::get('_old', []);
        Session::forget('_old');
        return is_array($old) ? $old : [];
    }

    protected function back(string $path): never
    {
        Response::redirect($path);
    }
}