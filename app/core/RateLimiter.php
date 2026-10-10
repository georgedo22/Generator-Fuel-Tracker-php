<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rate limiter backed by files under storage/rate-limit so that limits
 * survive session destruction and cannot be trivially bypassed by clearing
 * cookies (unlike a purely session-based limiter).
 */
final class RateLimiter
{
    /** Returns true when the action is allowed, false when throttled. */
    public static function check(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool
    {
        $file = self::file($key);
        $now = time();

        $data = ['attempts' => 0, 'expires_at' => $now + $decaySeconds];
        if (is_file($file)) {
            $decoded = json_decode((string) @file_get_contents($file), true);
            if (is_array($decoded) && isset($decoded['attempts'], $decoded['expires_at'])) {
                $data = $decoded;
            }
        }

        // Window expired: start over.
        if ($now > (int) $data['expires_at']) {
            $data = ['attempts' => 0, 'expires_at' => $now + $decaySeconds];
        }

        $data['attempts'] = (int) $data['attempts'] + 1;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return $data['attempts'] <= $maxAttempts;
    }

    /** Seconds remaining in the current throttle window (0 when not limited). */
    public static function retryAfter(string $key): int
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return 0;
        }
        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded) || !isset($decoded['expires_at'])) {
            return 0;
        }
        return max(0, (int) $decoded['expires_at'] - time());
    }

    /** Clear the counter for a key (e.g. after a successful login). */
    public static function clear(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function file(string $key): string
    {
        $dir = PROJECT_ROOT . '/storage/rate-limit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir . '/' . sha1($key) . '.json';
    }
}
