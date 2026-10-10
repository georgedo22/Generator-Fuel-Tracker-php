<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PDO connection singleton.
 */
final class Database
{
    private static ?\PDO $pdo = null;

    public static function connection(): \PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }

        // Optional explicit DSN override (e.g. sqlite for local demos/tests).
        $dsnOverride = config('db.dsn');

        if (is_string($dsnOverride) && $dsnOverride !== '') {
            $dsn  = $dsnOverride;
            $user = (string) config('db.user', '');
            $pass = (string) config('db.pass', '');
        } else {
            $host = (string) config('db.host', 'localhost');
            $name = (string) config('db.name', '');
            $user = (string) config('db.user', '');
            $pass = (string) config('db.pass', '');
            $charset = (string) config('db.charset', 'utf8mb4');
            $port = (int) config('db.port', 3306);

            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);
        }

        self::$pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }

    public static function beginTransaction(): void
    {
        if (!self::connection()->inTransaction()) {
            self::connection()->beginTransaction();
        }
    }

    public static function commit(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->commit();
        }
    }

    public static function rollBack(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }
}