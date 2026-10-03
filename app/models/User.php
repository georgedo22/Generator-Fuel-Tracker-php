<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User model.
 */
final class User extends Model
{
    /** @return array<string,mixed>|false */
    public static function find(string $id): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** @return array<string,mixed>|false */
    public static function findByEmail(string $email): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $stmt = self::db()->query(
            'SELECT id, name, email, role, created_at FROM users ORDER BY created_at ASC'
        );
        return $stmt->fetchAll();
    }

    public static function emailExists(string $email, ?string $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $stmt = self::db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $stmt->execute([$email, $exceptId]);
        } else {
            $stmt = self::db()->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public static function create(string $name, string $email, string $password, string $role): string
    {
        $id = self::uuid();
        $stmt = self::db()->prepare(
            'INSERT INTO users (id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $name, $email, password_hash($password, PASSWORD_BCRYPT), $role]);
        return $id;
    }

    public static function update(string $id, string $name, string $email, string $role, ?string $password): void
    {
        if ($password !== null && $password !== '') {
            $stmt = self::db()->prepare(
                'UPDATE users SET name = ?, email = ?, role = ?, password_hash = ? WHERE id = ?'
            );
            $stmt->execute([$name, $email, $role, password_hash($password, PASSWORD_BCRYPT), $id]);
        } else {
            $stmt = self::db()->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
            $stmt->execute([$name, $email, $role, $id]);
        }
    }

    public static function delete(string $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}