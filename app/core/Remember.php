<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * "Remember me" authentication via a secure, hashed cookie.
 *
 * Cookie format:  <user_id>:<plain selector+validator token>
 * Stored in DB:   sha256(token) only — the raw token never touches storage,
 *                 so a database leak cannot be replayed as a valid cookie.
 *
 * Tokens are single-use: every successful auto-login rotates the cookie
 * (new random token, old hash invalidated). A stolen token therefore works
 * at most once before the legitimate user's next visit invalidates it.
 */
final class Remember
{
    public const COOKIE = 'gt_remember';
    private const DAYS = 30;

    /** Issue a remember-me cookie for the given user (call right after login). */
    public static function issue(string $userId): void
    {
        self::ensureTable();

        // Invalidate any previous token for this user (one active cookie per user).
        $del = Database::connection()->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $del->execute([$userId]);

        $plain = bin2hex(random_bytes(32));            // 64-char hex token
        $hash = hash('sha256', $plain);
        $expiresAt = time() + self::DAYS * 86400;

        $ins = Database::connection()->prepare(
            'INSERT INTO remember_tokens (token_hash, user_id, expires_at) VALUES (?, ?, ?)'
        );
        $ins->execute([$hash, $userId, date('Y-m-d H:i:s', $expiresAt)]);

        self::setCookie($userId . ':' . $plain, $expiresAt);
    }

    /** Clear the cookie and stored token (call on logout). */
    public static function clear(): void
    {
        $raw = $_COOKIE[self::COOKIE] ?? '';
        if ($raw !== '') {
            $parts = explode(':', $raw, 2);
            if (count($parts) === 2 && strlen($parts[1]) === 64) {
                self::ensureTable();
                $stmt = Database::connection()->prepare('DELETE FROM remember_tokens WHERE token_hash = ?');
                $stmt->execute([hash('sha256', $parts[1])]);
            }
        }
        self::setCookie('', time() - 3600);
    }

    /**
     * If a valid remember cookie is present but no session exists, restore
     * the session from it (rotating the token). Returns true when restored.
     */
    public static function attemptSessionRestore(): bool
    {
        if (Session::get('user_id') !== null) {
            return false; // Already logged in normally.
        }

        $raw = $_COOKIE[self::COOKIE] ?? '';
        if ($raw === '' || substr_count($raw, ':') !== 1) {
            return false;
        }
        [$userId, $plain] = explode(':', $raw, 2);
        if (!preg_match('/^[0-9a-f]{32,36}$/i', $userId) || !preg_match('/^[0-9a-f]{64}$/', $plain)) {
            self::clear();
            return false;
        }

        self::ensureTable();
        $hash = hash('sha256', $plain);

        $stmt = Database::connection()->prepare(
            'SELECT user_id, expires_at FROM remember_tokens WHERE token_hash = ? LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();

        if (!$row) {
            // Unknown or already-consumed token. If the user id still has an
            // active token, this looks like a replay of a stolen single-use
            // token — destroy all tokens for that user to stay safe.
            $check = Database::connection()->prepare('SELECT 1 FROM remember_tokens WHERE user_id = ? LIMIT 1');
            $check->execute([$userId]);
            if ($check->fetchColumn()) {
                $purge = Database::connection()->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
                $purge->execute([$userId]);
                self::clear();
            }
            return false;
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            $del = Database::connection()->prepare('DELETE FROM remember_tokens WHERE token_hash = ?');
            $del->execute([$hash]);
            self::clear();
            return false;
        }

        $user = User::find($userId);
        if (!$user) {
            self::clear();
            return false;
        }

        // Restore the session exactly like a normal login would…
        Auth::login($user);
        // …then rotate the cookie (single-use tokens).
        self::issue($userId);
        return true;
    }

    private static function setCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (bool) config('app.cookie_secure', false),
        ]);
    }

    /** Create the tokens table on first use (works with MySQL and SQLite). */
    private static function ensureTable(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $pdo = Database::connection();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('CREATE TABLE IF NOT EXISTS remember_tokens (
                token_hash VARCHAR(64) NOT NULL PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL
            )');
            return;
        }

        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS `remember_tokens` (
                `token_hash` VARCHAR(64) NOT NULL,
                `user_id`    CHAR(36) NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `expires_at` DATETIME NOT NULL,
                PRIMARY KEY (`token_hash`),
                KEY `remember_user_idx` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        } catch (\Throwable) {
            // Table creation may be unavailable (permissions); auth still works.
        }
    }
}
