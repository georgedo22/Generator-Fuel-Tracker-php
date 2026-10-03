<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentication + guards.
 */
final class Auth
{
    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        $id = Session::get('user_id');
        if (!is_string($id) && !is_int($id)) {
            return null;
        }
        $user = User::find((string) $id);
        return $user ?: null;
    }

    public static function check(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function id(): ?string
    {
        $id = Session::get('user_id');
        return $id !== null ? (string) $id : null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'ADMIN';
    }

    /** @param array<string,mixed> $user */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (string) $user['id']);
        Session::set('user_name', (string) $user['name']);
        Session::set('user_role', (string) $user['role']);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Flash::error('Please sign in to continue.');
            Response::redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Forbidden'], 'layouts/guest');
            exit;
        }
    }

    /** Verify a CSRF token for state-changing requests. */
    public static function requireCsrf(): void
    {
        $token = $_POST['_csrf'] ?? null;
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            http_response_code(419);
            echo View::render('errors/419', ['title' => 'Page Expired'], 'layouts/guest');
            exit;
        }
    }
}