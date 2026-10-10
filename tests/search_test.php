<?php

declare(strict_types=1);

/**
 * Search-feature tests:
 *  - User::all($search) filters by email (LIKE, parameterized).
 *  - Views expose the searchable generator dropdowns (logs/reports) and the
 *    users email search box.
 *  - searchable-select.js asset exists and is wired into the layout.
 *
 * Run:  php tests/search_test.php   (requires pdo_sqlite)
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Models\User;

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
$pdo->exec('CREATE TABLE users (
    id TEXT PRIMARY KEY, name TEXT, email TEXT UNIQUE,
    password_hash TEXT, role TEXT DEFAULT "OPERATOR",
    created_at TEXT DEFAULT CURRENT_TIMESTAMP)');

// Swap the app's DB singleton onto this connection via reflection.
$dbClass = new ReflectionClass(\App\Core\Database::class);
$prop = $dbClass->getProperty('pdo');
$prop->setAccessible(true);
$prop->setValue(null, $pdo);

User::create('Alice One', 'alice@example.com', 'secret123', 'ADMIN');
User::create('Bob Two', 'bob@company.org', 'secret123', 'OPERATOR');
User::create('Carol Three', 'carol.a@example.com', 'secret123', 'OPERATOR');

// --- No search returns everyone ---
check('all() without search returns all users', count(User::all()) === 3);
check('all(null) returns all users', count(User::all(null)) === 3);
check('all("") returns all users', count(User::all('')) === 3);
check('all("   ") returns all users (whitespace trimmed)', count(User::all('   ')) === 3);

// --- Email substring search ---
$res = User::all('example.com');
check('search "example.com" matches 2 users', count($res) === 2, 'got=' . count($res));

$res = User::all('alice');
check('search "alice" matches exactly 1 user', count($res) === 1 && $res[0]['email'] === 'alice@example.com');

$res = User::all('ALICE@EXAMPLE.COM');
check('search is case-insensitive on SQLite', count($res) === 1, 'got=' . count($res));

$res = User::all('carol.a');
check('search "carol.a" matches carol only', count($res) === 1 && $res[0]['email'] === 'carol.a@example.com');

$res = User::all('nobody@');
check('non-matching search returns empty list', $res === []);

// --- LIKE wildcards in the query are treated as literals-ish but must not error ---
$res = User::all('%');
check('search "%" does not crash', is_array($res));

// --- SQL injection attempt is safely parameterized ---
$res = User::all("' OR 1=1 --");
check("injection attempt returns no rows (parameterized)", $res === [], 'got=' . json_encode($res));

// --- View wiring ---
$logsView = file_get_contents(__DIR__ . '/../app/views/logs/index.php');
check('logs filter generator select is data-searchable', substr_count($logsView, 'data-searchable') >= 2, 'filter + modal selects');

$reportsView = file_get_contents(__DIR__ . '/../app/views/reports/index.php');
check('reports generator select is data-searchable', str_contains($reportsView, 'name="generator_id" data-searchable'));

$usersView = file_get_contents(__DIR__ . '/../app/views/users/index.php');
check('users page has email search input', str_contains($usersView, 'name="q"') && str_contains($usersView, 'Search by email'));

$layout = file_get_contents(__DIR__ . '/../app/views/layouts/app.php');
check('layout loads searchable-select.js', str_contains($layout, "asset('searchable-select.js')"));

check('searchable-select.js asset exists', is_file(__DIR__ . '/../public/assets/searchable-select.js'));

$js = file_get_contents(__DIR__ . '/../public/assets/searchable-select.js');
check('js enhances select[data-searchable]', str_contains($js, "select[data-searchable]"));

echo "\n" . ($failed === 0 ? "ALL SEARCH TESTS PASSED ({$passed})\n" : "FAILED: {$failed} of " . ($passed + $failed) . "\n");
exit($failed === 0 ? 0 : 1);
