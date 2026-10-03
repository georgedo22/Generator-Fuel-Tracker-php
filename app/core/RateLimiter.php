<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Basic session-based rate limiter.
 */
final class RateLimiter
{
    public static function check(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool
    {
        $sessionKey = 'rate_limit_' . $key;
        $data = Session::get($sessionKey, ['attempts' => 0, 'expires_at' => 0]);
        
        if (time() > $data['expires_at']) {
            $data = ['attempts' => 0, 'expires_at' => time() + $decaySeconds];
        }
        
        $data['attempts']++;
        Session::set($sessionKey, $data);
        
        return $data['attempts'] <= $maxAttempts;
    }
}
