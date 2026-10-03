<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Generator model.
 */
final class Generator extends Model
{
    /** @return array<int,array<string,mixed>> */
    public static function all(?string $search = null): array
    {
        $sql = 'SELECT g.*, (SELECT COUNT(*) FROM fuel_logs fl WHERE fl.generator_id = g.id) AS fuel_logs_count
                FROM generators g';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' WHERE g.code LIKE ? OR g.location LIKE ?';
            $like = '%' . $search . '%';
            $params = [$like, $like];
        }
        $sql .= ' ORDER BY g.created_at DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function allSimple(): array
    {
        $stmt = self::db()->query('SELECT id, code, location FROM generators ORDER BY code ASC');
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|false */
    public static function find(string $id): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM generators WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** @return array<string,mixed>|false */
    public static function findByCode(string $code): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM generators WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public static function codeExists(string $code, ?string $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $stmt = self::db()->prepare('SELECT 1 FROM generators WHERE code = ? AND id <> ? LIMIT 1');
            $stmt->execute([$code, $exceptId]);
        } else {
            $stmt = self::db()->prepare('SELECT 1 FROM generators WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public static function create(string $code, string $location, ?string $imageUrl, ?string $notes): string
    {
        $id = self::uuid();
        $stmt = self::db()->prepare(
            'INSERT INTO generators (id, code, location, image_url, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $code, $location, $imageUrl, $notes]);
        return $id;
    }

    public static function update(string $id, string $code, string $location, ?string $imageUrl, ?string $notes): void
    {
        $stmt = self::db()->prepare(
            'UPDATE generators SET code = ?, location = ?, image_url = ?, notes = ? WHERE id = ?'
        );
        $stmt->execute([$code, $location, $imageUrl, $notes, $id]);
    }

    public static function delete(string $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM generators WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** All generators with their logs, for export/summaries. */
    /** @return array<int,array<string,mixed>> */
    public static function allWithCounts(): array
    {
        $stmt = self::db()->query(
            'SELECT g.*, (SELECT COUNT(*) FROM fuel_logs fl WHERE fl.generator_id = g.id) AS fuel_logs_count
             FROM generators g ORDER BY g.created_at DESC'
        );
        return $stmt->fetchAll();
    }
}