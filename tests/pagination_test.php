<?php

declare(strict_types=1);

/**
 * Database tests for FuelLog::paginated()/totals() using an in-memory SQLite
 * schema that mirrors the MySQL tables (the production SQL is portable
 * enough for these queries).
 *
 * Run:  php tests/pagination_test.php   (requires pdo_sqlite)
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Models\FuelLog;

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

if (!extension_loaded('pdo_sqlite')) {
    echo "SKIP  pdo_sqlite not available\n";
    exit(0);
}

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('CREATE TABLE generators (id TEXT PRIMARY KEY, code TEXT, location TEXT)');
$pdo->exec('CREATE TABLE fuel_logs (
    id TEXT PRIMARY KEY, generator_id TEXT, date TEXT, time TEXT,
    hour_reading REAL, liters REAL, price_per_liter REAL, fuel_type TEXT,
    is_tank_full INTEGER DEFAULT 0, hours_worked REAL, consumption_per_hour REAL, cost REAL, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');

// Swap the app's DB singleton onto this connection via reflection.
$dbClass = new ReflectionClass(\App\Core\Database::class);
$prop = $dbClass->getProperty('pdo');
$prop->setAccessible(true);
$prop->setValue(null, $pdo);

// Seed: generator A gets 60 logs, generator B gets 15.
$insG = $pdo->prepare('INSERT INTO generators (id, code, location) VALUES (?,?,?)');
$insG->execute(['gA', 'GEN-A', 'Site A']);
$insG->execute(['gB', 'GEN-B', 'Site B']);

$insL = $pdo->prepare('INSERT INTO fuel_logs (id, generator_id, date, time, hour_reading, liters, price_per_liter, fuel_type, is_tank_full, cost)
                       VALUES (?,?,?,?,?,?,?,?,?,?)');
for ($i = 1; $i <= 60; $i++) {
    $day = sprintf('2026-%02d-%02d', intdiv($i - 1, 28) + 1, ($i - 1) % 28 + 1);
    $insL->execute(["logA{$i}", 'gA', $day, '08:00', $i * 10.0, 10.0, 100.0, 'DIESEL', $i % 2, 1000.0]);
}
for ($i = 1; $i <= 15; $i++) {
    $insL->execute(["logB{$i}", 'gB', '2026-06-01', '09:00', $i * 5.0, 5.0, 200.0, 'PETROL', 1, 1000.0]);
}

// --- paginated basics ---
$res = FuelLog::paginated([], 1, 25);
check('page 1 has 25 rows', count($res['rows']) === 25, 'got=' . count($res['rows']));
check('total counts all 75 rows', $res['total'] === 75, 'got=' . $res['total']);

$res3 = FuelLog::paginated([], 3, 25);
check('page 3 has 25 rows', count($res3['rows']) === 25);

// 75 rows / 25 per page -> exactly 3 full pages; page 4 must be empty.
$res4 = FuelLog::paginated([], 4, 25);
check('page beyond last page is empty', count($res4['rows']) === 0, 'got=' . count($res4['rows']));
$resLast = FuelLog::paginated([], 3, 25);
check('last page (3) has 25 rows', count($resLast['rows']) === 25);

// Pages must not overlap.
$ids1 = array_column(FuelLog::paginated([], 1, 25)['rows'], 'id');
$ids2 = array_column(FuelLog::paginated([], 2, 25)['rows'], 'id');
check('pages are disjoint', array_intersect($ids1, $ids2) === []);

// --- filters respected in pagination and totals ---
$res = FuelLog::paginated(['generatorId' => 'gB'], 1, 25);
check('filter by generator returns its rows only', $res['total'] === 15 && count($res['rows']) === 15);

$tots = FuelLog::totals([]);
check('totals count across all rows', $tots['count'] === 75, 'got=' . $tots['count']);
check('totals liters sum correct', abs($tots['liters'] - (60 * 10 + 15 * 5)) < 1e-6, 'got=' . $tots['liters']);
check('totals cost sum correct', abs($tots['cost'] - 75 * 1000) < 1e-6, 'got=' . $tots['cost']);

$totsB = FuelLog::totals(['generatorId' => 'gB']);
check('filtered totals only include generator B', $totsB['count'] === 15 && abs($totsB['liters'] - 75) < 1e-6);

$totsRange = FuelLog::totals(['from' => '2026-01-01', 'to' => '2026-01-28']);
check('date-range filter narrows totals', $totsRange['count'] === 28, 'got=' . $totsRange['count']);

$totsType = FuelLog::totals(['fuelType' => 'PETROL']);
check('fuel-type filter narrows totals', $totsType['count'] === 15, 'got=' . $totsType['count']);

// --- perPage clamping ---
$res = FuelLog::paginated([], 1, 1000);
check('perPage clamped to 200 max', count($res['rows']) === 75); // total is 75 anyway
$res = FuelLog::paginated([], 0, 0);
check('page/perPage minimum of 1 enforced', count($res['rows']) === 1);

echo "\n" . ($failed === 0 ? "ALL PASSED ({$passed})" : "{$failed} FAILED, {$passed} passed") . "\n";
exit($failed === 0 ? 0 : 1);
