<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Flash messages stored in the session.
 */
final class Flash
{
    public static function set(string $type, string $message): void
    {
        $messages = Session::get('_flash', []);
        $messages[] = ['type' => $type, 'message' => $message];
        Session::set('_flash', $messages);
    }

    public static function success(string $message): void
    {
        self::set('success', $message);
    }

    public static function error(string $message): void
    {
        self::set('error', $message);
    }

    public static function pull(): array
    {
        $messages = Session::get('_flash', []);
        Session::forget('_flash');
        return is_array($messages) ? $messages : [];
    }
}