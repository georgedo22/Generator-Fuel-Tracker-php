<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Shared view helpers for formatting and rendering.
 */
final class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function naira(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $symbol = (string) config('app.currency_symbol', '₦');
        return $symbol . number_format((float) $value, 2);
    }

    public static function number(float|int|string|null $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        return rtrim(rtrim(number_format((float) $value, $decimals), '0'), '.') ?: '0';
    }

    public static function date(?string $value): string
    {
        if (!$value) {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date('d M Y', $ts) : (string) $value;
    }

    public static function fuelLabel(string $type): string
    {
        return match ($type) {
            'PETROL' => 'Petrol',
            'DIESEL' => 'Diesel',
            'KEROSENE' => 'Kerosene',
            default => $type,
        };
    }

    /** Old input value for form repopulation. */
    public static function old(array $old, string $key, string $default = ''): string
    {
        return htmlspecialchars((string) ($old[$key] ?? $default), ENT_QUOTES, 'UTF-8');
    }

    /** Build a public URL for an uploaded image, respecting a subdirectory install. */
    public static function imageUrl(?string $url): string
    {
        if (!$url) {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (function_exists('url')) {
            return url($url);
        }
        return $url;
    }
}