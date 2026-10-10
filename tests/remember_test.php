<?php

declare(strict_types=1);

/**
 * "Remember me for 30 days" feature tests.
 *
 * Part A (no DB): unit checks on the login form + controller wiring.
 * Part B (SQLite, temp file): full end-to-end flow — issue cookie, expire
 *   the session, auto-restore, single-use rotation, replay invalidation,
 *   logout clears the token.
 *
 * Run:  php tests/remember_test.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Remember;
use App\Core\Session;
use App\Models\User;

$passed = 0;
$failed = 0;

/** Generate a known token row for $uid and return its plaintext (test helper). */
function craft_token(\PDO $pdo, string $uid): string
{
    $plain = bin2hex(random_bytes(32));
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$uid]);
    $pdo->prepare('INSERT INTO remember_tokens (token_hash, user_id, expires_at) VALUES (?, ?, ?)')
        ->execute([hash('sha256', $plain), $uid, date('Y-m-d H:i:s', time() + 30 * 86400)]);
    return $plain;
}

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

// ===========================================================================
// Part A — static wiring checks (no DB needed)
// ===========================================================================
$loginView = file_get_contents(APP_ROOT . '/views/auth/login.php');
check('login form has remember-me checkbox', str_contains($loginView, 'name="remember-me"'));

$controllerSrc = file_get_contents(APP_ROOT . '/controllers/AuthController.php');
check('AuthController reads remember-me on login', str_contains($controllerSrc, "empty(\$_POST['remember-me'])"));
check('AuthController issues cookie after login', str_contains($controllerSrc, 'Remember::issue('));
check('AuthController clears cookie on logout', str_contains($controllerSrc, 'Remember::clear()'));

$indexSrc = file_get_contents(dirname(__DIR__) . '/public/index.php');
check('front controller attempts session restore', str_contains($indexSrc, 'Remember::attemptSessionRestore()'));

$schemaSrc = file_get_contents(base_path('database/schema.sql'));
check('schema defines remember_tokens table', str_contains($schemaSrc, 'CREATE TABLE IF NOT EXISTS `remember_tokens`'));

// ===========================================================================
// Part B — functional test against a throwaway SQLite database.
// The app config supports a `db.dsn` override, so we drop in a tiny
// config.local.php for the duration of this test and remove it afterwards.
// ===========================================================================
$dbFile = tempnam(sys_get_temp_dir(), 'gt_remember_') . '.sqlite';
$localCfg = APP_ROOT . '/config/config.local.php';
$hadjLocal = is_file($localCfg);
if (!$hadjLocal) {
    file_put_contents($localCfg, '<?php return ["db" => ["dsn" => "sqlite:' . $dbFile . '"]];');
}

try {
    $pdo = Database::connection();
    check('test DB is SQLite', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite');

    // Recreate the users + remember_tokens tables in SQLite.
    $pdo->exec('CREATE TABLE users (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role TEXT NOT NULL DEFAULT "OPERATOR",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $userId = User::uuid();
    $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)')
        ->execute([$userId, 'Test User', 'rm-test@example.com', password_hash('secret123', PASSWORD_BCRYPT), 'ADMIN']);

    // --- issue() creates a hashed token with ~30 day expiry ------------------
    Remember::issue($userId); // also exercises CREATE TABLE IF NOT EXISTS
    $row = $pdo->query('SELECT * FROM remember_tokens')->fetch();
    check('token row created on issue()', $row !== false);
    check('stored value is a sha256 hash (64 hex chars)',
        is_array($row) && preg_match('/^[0-9a-f]{64}$/', (string) $row['token_hash']) === 1);
    check('expiry is ~30 days ahead',
        is_array($row) && strtotime((string) $row['expires_at']) >= time() + 29 * 86400);

    // Only the hash is stored, so to simulate the browser cookie we craft a
    // known token directly (see craft_token above), mimicking what issue()
    // does internally.

    // --- No session + valid cookie => session restored -----------------------
    $plain = craft_token($pdo, $userId);
    $_COOKIE[Remember::COOKIE] = $userId . ':' . $plain;
    $_SESSION = [];

    $restored = Remember::attemptSessionRestore();
    check('valid remember cookie restores session', $restored === true);
    check('user_id present in session after restore', Session::get('user_id') === $userId);
    check('Auth::check() true after restore', Auth::check() === true);

    // --- Token rotated: old hash must be gone --------------------------------
    $newRow = $pdo->query('SELECT token_hash FROM remember_tokens')->fetch();
    check('old token hash replaced by rotated one',
        $newRow && $newRow['token_hash'] !== hash('sha256', $plain));

    // Attacker replays the STOLEN original token while a newer one exists.
    $_SESSION = []; // victim's session expired
    $_COOKIE[Remember::COOKIE] = $userId . ':' . $plain; // stolen old token
    $replay = Remember::attemptSessionRestore();
    check('replayed (consumed) token rejected', $replay === false);
    $remaining = (int) $pdo->query('SELECT COUNT(*) FROM remember_tokens WHERE user_id = ' . $pdo->quote($userId))->fetchColumn();
    check('all tokens purged for user after replay detected', $remaining === 0);

    // --- Malformed cookies are safely ignored --------------------------------
    foreach (['garbage', 'nocolonhere', $userId . ':' . str_repeat('z', 64), $userId . ':short'] as $bad) {
        $_SESSION = [];
        $_COOKIE[Remember::COOKIE] = $bad;
        check('malformed cookie rejected: ' . substr($bad, 0, 18) . '…',
            Remember::attemptSessionRestore() === false && Session::get('user_id') === null);
    }

    // --- Expired token rejected -----------------------------------------------
    $plain2 = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO remember_tokens (token_hash, user_id, expires_at) VALUES (?, ?, ?)')
        ->execute([hash('sha256', $plain2), $userId, date('Y-m-d H:i:s', time() - 60)]);
    $_SESSION = [];
    $_COOKIE[Remember::COOKIE] = $userId . ':' . $plain2;
    check('expired token rejected', Remember::attemptSessionRestore() === false);
    check('expired token deleted from DB',
        (int) $pdo->query('SELECT COUNT(*) FROM remember_tokens')->fetchColumn() === 0);

    // --- Already logged-in user: restore is a no-op ---------------------------
    Session::set('user_id', 'other-id');
    $_COOKIE[Remember::COOKIE] = 'anything:whatever';
    check('existing session untouched by restore',
        Remember::attemptSessionRestore() === false && Session::get('user_id') === 'other-id');

    // --- clear() removes the stored token --------------------------------------
    Session::forget('user_id');
    $plain3 = craft_token($pdo, $userId);
    $_COOKIE[Remember::COOKIE] = $userId . ':' . $plain3;
    Remember::clear();
    check('clear() deletes stored token',
        (int) $pdo->query('SELECT COUNT(*) FROM remember_tokens')->fetchColumn() === 0);
    unset($_COOKIE[Remember::COOKIE]);
} finally {
    if (!$hadjLocal) {
        @unlink($localCfg);
    }
    @unlink($dbFile);
}

echo "\n" . ($failed === 0 ? "ALL PASSED ({$passed})" : "{$failed} FAILED, {$passed} passed") . "\n";
exit($failed === 0 ? 0 : 1);
