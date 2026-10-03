<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Base model with shared helpers.
 */
abstract class Model
{
    protected static function db(): \PDO
    {
        return Database::connection();
    }

    /** Generate a RFC-4122 v4 UUID (compatible with CHAR(36) ids). */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}