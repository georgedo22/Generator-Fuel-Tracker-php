<?php

declare(strict_types=1);

/**
 * HTTP smoke tests against a running server.
 *
 *   php -S 127.0.0.1:8123 -t public public/index.php &
 *   php tests/smoke_test.php [base-url]
 *
 * Requires no database writes; pages that need a DB simply must not return
 * 500 with error details leaked.
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8123', '/');

$passed = 0;
$failed = 0;

function request(string $url): array
{
    $ctx = stream_context_create(['http' => [
        'follow_location' => 0,
        'ignore_errors' => true,
        'timeout' => 10,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    return [$status, $body === false ? '' : $body];
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

// 1. Root redirects to login for guests.
[$status, $body, ] = [0, '', ''];
$res = request($base . '/');
check('GET / redirects (302)', $res[0] === 302, "got={$res[0]}");

// 2. Login page renders.
$res = request($base . '/login');
check('GET /login returns 200', $res[0] === 200, "got={$res[0]}");
check('login page contains the form', str_contains($res[1], 'name="password"'));
check('login page contains a CSRF field', str_contains($res[1], 'name="_csrf"'));

// 3. Protected pages redirect guests.
foreach (['/dashboard', '/logs', '/reports', '/generators', '/users'] as $path) {
    $res = request($base . $path);
    check("guest GET {$path} is redirected", in_array($res[0], [302, 419, 403], true), "got={$res[0]}");
}

// 4. Unknown route returns 404 (not a PHP error).
$res = request($base . '/definitely-not-a-route-xyz');
check('unknown route returns 404', $res[0] === 404, "got={$res[0]}");

// 5. No stack traces leaked in production mode.
$res = request($base . '/definitely-not-a-route-xyz');
check('no fatal-error leakage', !str_contains($res[1], 'Stack trace') && !str_contains($res[1], 'Warning:'));

echo "\n" . ($failed === 0 ? "ALL PASSED ({$passed})" : "{$failed} FAILED, {$passed} passed") . "\n";
exit($failed === 0 ? 0 : 1);
