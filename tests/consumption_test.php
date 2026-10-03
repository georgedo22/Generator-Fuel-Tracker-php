<?php

declare(strict_types=1);

/**
 * Standalone checks for the full-to-full consumption algorithm.
 * Run:  php tests/consumption_test.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Services\ConsumptionService;

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS  {$name}\n";
    } else {
        $failed++;
        echo "FAIL  {$name} {$detail}\n";
    }
}

function mklog(string $id, float $hour, float $liters, bool $full, float $price = 100.0): array
{
    return [
        'id' => $id,
        'hour_reading' => $hour,
        'liters' => $liters,
        'price_per_liter' => $price,
        'is_tank_full' => $full ? 1 : 0,
    ];
}

// 1. Non-full logs never compute.
$r = ConsumptionService::compute([mklog('a', 100, 20, false)]);
check('non-full log consumption is null', $r[0]['consumptionPerHour'] === null);

// 2. First full log is the series start.
$r = ConsumptionService::compute([mklog('a', 100, 40, true)]);
check('first full log consumption is null', $r[0]['consumptionPerHour'] === null);

// 3. Two consecutive full logs.
$r = ConsumptionService::compute([mklog('a', 1000, 0, true), mklog('b', 1100, 50, true)]);
$b = array_values(array_filter($r, fn ($x) => $x['id'] === 'b'))[0];
check('two full logs -> 0.5 L/h', abs($b['consumptionPerHour'] - 0.5) < 1e-9, "got={$b['consumptionPerHour']}");
check('two full logs -> 100 hours', abs($b['hoursWorked'] - 100) < 1e-9, "got={$b['hoursWorked']}");

// 4. Non-full liters included in the following full log.
$r = ConsumptionService::compute([
    mklog('a', 1000, 0, true),
    mklog('b', 1050, 20, false),
    mklog('c', 1100, 30, true),
]);
$b = array_values(array_filter($r, fn ($x) => $x['id'] === 'b'))[0];
$c = array_values(array_filter($r, fn ($x) => $x['id'] === 'c'))[0];
check('non-full middle log has null consumption', $b['consumptionPerHour'] === null);
check('full log includes non-full liters (0.5)', abs($c['consumptionPerHour'] - 0.5) < 1e-9, "got={$c['consumptionPerHour']}");

// 5. Non-full logs before the first full log are ignored.
$r = ConsumptionService::compute([
    mklog('a', 900, 15, false),
    mklog('b', 1000, 0, true),
    mklog('c', 1050, 25, true),
]);
$c = array_values(array_filter($r, fn ($x) => $x['id'] === 'c'))[0];
check('ignores non-full logs before series start (0.5)', abs($c['consumptionPerHour'] - 0.5) < 1e-9, "got={$c['consumptionPerHour']}");

// 6. No divide by zero.
$r = ConsumptionService::compute([mklog('a', 1000, 0, true), mklog('b', 1000, 10, true)]);
$b = array_values(array_filter($r, fn ($x) => $x['id'] === 'b'))[0];
check('zero hours yields null consumption', $b['consumptionPerHour'] === null);
check('zero hours recorded as 0', $b['hoursWorked'] === 0.0, "got={$b['hoursWorked']}");

// 7. Cost = liters * price.
$r = ConsumptionService::compute([mklog('a', 100, 30, false, 850)]);
check('cost = liters * price', abs($r[0]['cost'] - 25500) < 1e-9, "got={$r[0]['cost']}");

// 8. Unordered input is sorted by hour reading.
$r = ConsumptionService::compute([
    mklog('c', 1100, 30, true),
    mklog('a', 1000, 0, true),
    mklog('b', 1050, 20, false),
]);
$c = array_values(array_filter($r, fn ($x) => $x['id'] === 'c'))[0];
check('sorts unordered logs (0.5)', abs($c['consumptionPerHour'] - 0.5) < 1e-9, "got={$c['consumptionPerHour']}");

echo "\n" . ($failed === 0 ? "ALL PASSED ({$passed})" : "{$failed} FAILED, {$passed} passed") . "\n";
exit($failed === 0 ? 0 : 1);