<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Fuel log model.
 */
final class FuelLog extends Model
{
    /**
     * Build the WHERE clause from filters.
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['generatorId'])) {
            $where[] = 'fl.generator_id = ?';
            $params[] = $filters['generatorId'];
        }
        if (!empty($filters['fuelType']) && in_array($filters['fuelType'], ['PETROL', 'DIESEL', 'KEROSENE'], true)) {
            $where[] = 'fl.fuel_type = ?';
            $params[] = $filters['fuelType'];
        }
        if (($filters['tank'] ?? null) === 'FULL') {
            $where[] = 'fl.is_tank_full = 1';
        } elseif (($filters['tank'] ?? null) === 'EMPTY') {
            $where[] = 'fl.is_tank_full = 0';
        }
        if (!empty($filters['from'])) {
            $where[] = 'fl.date >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'fl.date <= ?';
            $params[] = $filters['to'];
        }

        $sql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        return [$sql, $params];
    }

    /** @return array<int,array<string,mixed>> */
    public static function filtered(array $filters = []): array
    {
        [$where, $params] = self::buildWhere($filters);
        $sql = 'SELECT fl.*, g.code AS generator_code, g.location AS generator_location
                FROM fuel_logs fl
                JOIN generators g ON g.id = fl.generator_id'
            . $where
            . ' ORDER BY fl.date DESC, fl.time DESC, fl.created_at DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|false */
    public static function find(string $id): array|false
    {
        $stmt = self::db()->prepare(
            'SELECT fl.*, g.code AS generator_code FROM fuel_logs fl
             JOIN generators g ON g.id = fl.generator_id WHERE fl.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lightweight rows for the consumption algorithm, ordered by hour reading.
     * @return array<int,array<string,mixed>>
     */
    public static function forConsumption(string $generatorId): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, hour_reading, liters, price_per_liter, is_tank_full
             FROM fuel_logs WHERE generator_id = ? ORDER BY hour_reading ASC, id ASC'
        );
        $stmt->execute([$generatorId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function byGenerator(string $generatorId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM fuel_logs WHERE generator_id = ? ORDER BY hour_reading ASC, id ASC'
        );
        $stmt->execute([$generatorId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): string
    {
        $id = self::uuid();
        $stmt = self::db()->prepare(
            'INSERT INTO fuel_logs
                (id, generator_id, date, time, hour_reading, liters, price_per_liter, fuel_type, is_tank_full, cost)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['generator_id'],
            $data['date'],
            $data['time'],
            $data['hour_reading'],
            $data['liters'],
            $data['price_per_liter'],
            $data['fuel_type'],
            $data['is_tank_full'] ? 1 : 0,
            $data['cost'],
        ]);
        return $id;
    }

    public static function update(string $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE fuel_logs
             SET generator_id = ?, date = ?, time = ?, hour_reading = ?, liters = ?,
                 price_per_liter = ?, fuel_type = ?, is_tank_full = ?, cost = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['generator_id'],
            $data['date'],
            $data['time'],
            $data['hour_reading'],
            $data['liters'],
            $data['price_per_liter'],
            $data['fuel_type'],
            $data['is_tank_full'] ? 1 : 0,
            $data['cost'],
            $id,
        ]);
    }

    public static function delete(string $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM fuel_logs WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function updateComputed(string $id, ?float $hoursWorked, ?float $consumptionPerHour, float $cost): void
    {
        $stmt = self::db()->prepare(
            'UPDATE fuel_logs SET hours_worked = ?, consumption_per_hour = ?, cost = ? WHERE id = ?'
        );
        $stmt->execute([$hoursWorked, $consumptionPerHour, $cost, $id]);
    }

    /** All logs joined with generator code, for dashboard/reports. */
    /** @return array<int,array<string,mixed>> */
    public static function allWithGenerator(): array
    {
        $stmt = self::db()->query(
            'SELECT fl.*, g.code AS generator_code
             FROM fuel_logs fl JOIN generators g ON g.id = fl.generator_id
             ORDER BY fl.date ASC, fl.time ASC'
        );
        return $stmt->fetchAll();
    }
}