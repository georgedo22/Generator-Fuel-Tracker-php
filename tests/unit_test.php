<?php

declare(strict_types=1);

/**
 * Unit tests for core helpers and services that do not need a database.
 * Run:  php tests/unit_test.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helpers;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Services\CsvService;

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

// --- Helpers::e (XSS escaping) ---
check('e() escapes quotes and tags',
    Helpers::e('<script>"a\'b"</script>') === '&lt;script&gt;&quot;a&#039;b&quot;&lt;/script&gt;',
    'got=' . Helpers::e('<script>"a\'b"</script>'));

// --- Helpers::number ---
check('number() formats with default decimals', Helpers::number(1234.5678) === '1,234.57');
check('number() trims trailing zeros', Helpers::number(10.500) === '10.5');
check('number(null) is em dash', Helpers::number(null) === '—');

// --- Helpers::naira ---
check('naira() prefixes symbol', Helpers::naira(1000) === '₦1,000.00');

// --- Helpers::fuelLabel ---
check('fuelLabel DIESEL', Helpers::fuelLabel('DIESEL') === 'Diesel');
check('fuelLabel unknown passthrough', Helpers::fuelLabel('XXX') === 'XXX');

// --- CsvService::toCsv ---
$csv = CsvService::toCsv(
    [['Name' => 'A,B', 'Qty' => 2], ['Name' => 'C"D', 'Qty' => 3]],
    ['Name', 'Qty']
);
check('toCsv contains BOM', str_starts_with($csv, "\xEF\xBB\xBF"));
check('toCsv quotes comma values', str_contains($csv, '"A,B"'), $csv);
check('toCsv escapes embedded quotes', str_contains($csv, '"C""D"'), $csv);
check('toCsv includes header row', str_contains($csv, 'Name,Qty'), $csv);

// --- CsvService::parseDate / parseTime ---
check('parseDate ISO passthrough', CsvService::parseDate('2026-10-03') === '2026-10-03');
try {
    CsvService::parseDate('not-a-date');
    check('parseDate rejects garbage', false);
} catch (\Throwable $e) {
    check('parseDate rejects garbage', true);
}
$time = CsvService::parseTime('14:30');
check('parseTime returns HH:MM', $time === '14:30', "got={$time}");

// --- RateLimiter (file-backed) ---
$key = 'test|' . bin2hex(random_bytes(6));
for ($i = 1; $i <= 3; $i++) {
    check("rate limiter allows attempt {$i}", RateLimiter::check($key, 3, 60));
}
check('rate limiter blocks attempt over the cap', !RateLimiter::check($key, 3, 60));
check('retryAfter reports remaining window', RateLimiter::retryAfter($key) > 0 && RateLimiter::retryAfter($key) <= 60);
RateLimiter::clear($key);
check('clear resets the counter', RateLimiter::check($key, 3, 60));
RateLimiter::clear($key);

// --- Csrf token lifecycle (CLI session works via $_SESSION) ---
$t1 = Csrf::token();
check('csrf token is 64 hex chars', (bool) preg_match('/^[0-9a-f]{64}$/', $t1));
check('csrf token is stable within session', Csrf::token() === $t1);
check('csrf verify accepts valid token', Csrf::verify($t1));
check('csrf verify rejects wrong token', !Csrf::verify('deadbeef'));
check('csrf verify rejects null', !Csrf::verify(null));

// --- Flash messages ---
Flash::error('boom');
Flash::success('ok');
$messages = Flash::pull();
check('flash stores two messages', count($messages) === 2);
check('flash preserves order/types',
    ($messages[0]['type'] ?? '') === 'error' && ($messages[1]['type'] ?? '') === 'success');
check('flash pull clears the queue', Flash::pull() === []);

echo "\n" . ($failed === 0 ? "ALL PASSED ({$passed})" : "{$failed} FAILED, {$passed} passed") . "\n";
exit($failed === 0 ? 0 : 1);
