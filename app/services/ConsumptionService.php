<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\FuelLog;

/**
 * Full-to-full fuel consumption algorithm.
 *
 * Logs are ordered by hour reading. A log where the tank is NOT full never
 * yields a consumption figure on its own. When a FULL log is reached, the
 * consumption is computed between it and the previous FULL log:
 *
 *   hoursWorked        = current.hourReading - previousFull.hourReading
 *   totalFuel          = current.liters + sum(liters of all non-full logs in between)
 *   consumptionPerHour = totalFuel / hoursWorked   (only when hoursWorked > 0)
 *
 * If there is no previous full log, the series has not started yet and no
 * consumption is computed.
 */
final class ConsumptionService
{
    /**
     * Pure calculation over a list of rows.
     *
     * @param array<int,array<string,mixed>> $logs rows with keys:
     *        id, hour_reading, liters, price_per_liter, is_tank_full
     * @return array<int,array{id:string,hoursWorked:?float,consumptionPerHour:?float,cost:float}>
     */
    public static function compute(array $logs): array
    {
        usort($logs, static function (array $a, array $b): int {
            $cmp = ((float) $a['hour_reading']) <=> ((float) $b['hour_reading']);
            return $cmp !== 0 ? $cmp : strcmp((string) $a['id'], (string) $b['id']);
        });

        $previousFullReading = null;
        $pendingNonFullLiters = 0.0;
        $results = [];

        foreach ($logs as $log) {
            $hourReading = (float) $log['hour_reading'];
            $liters = (float) $log['liters'];
            $cost = $liters * (float) $log['price_per_liter'];
            $isFull = (bool) $log['is_tank_full'];

            if ($isFull) {
                $hoursWorked = null;
                $consumption = null;

                if ($previousFullReading !== null) {
                    $hoursWorked = $hourReading - $previousFullReading;
                    if ($hoursWorked > 0) {
                        $totalFuel = $liters + $pendingNonFullLiters;
                        $consumption = $totalFuel / $hoursWorked;
                    } else {
                        $consumption = null;
                    }
                }

                $results[] = [
                    'id' => (string) $log['id'],
                    'hoursWorked' => $hoursWorked,
                    'consumptionPerHour' => $consumption,
                    'cost' => $cost,
                ];
                $previousFullReading = $hourReading;
                $pendingNonFullLiters = 0.0;
            } else {
                $results[] = [
                    'id' => (string) $log['id'],
                    'hoursWorked' => null,
                    'consumptionPerHour' => null,
                    'cost' => $cost,
                ];
                $pendingNonFullLiters += $liters;
            }
        }

        return $results;
    }

    /** Recompute and persist derived fields for every log of a generator. */
    public static function recomputeGenerator(string $generatorId): void
    {
        $logs = FuelLog::forConsumption($generatorId);
        $computed = self::compute($logs);

        if ($computed === []) {
            return;
        }

        Database::beginTransaction();
        try {
            foreach ($computed as $row) {
                FuelLog::updateComputed(
                    $row['id'],
                    $row['hoursWorked'],
                    $row['consumptionPerHour'],
                    $row['cost']
                );
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /** Recompute every generator (used after bulk import). */
    public static function recomputeAll(array $generatorIds): void
    {
        foreach (array_unique($generatorIds) as $id) {
            self::recomputeGenerator((string) $id);
        }
    }
}