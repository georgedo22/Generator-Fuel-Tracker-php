<?php

declare(strict_types=1);

/**
 * End-to-end login flow test (CSRF + rate limiting) against a running server.
 * No database is required: the limiter/CSRF checks fire before any DB access.
 *
 *   php -S 127.0.0.1:8123 -t public public/index.php &
 *   php tests/auth_flow_test.php [base-url]
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8123', '/');

$passed = 0;
$failed = 0;
$cookieJar = '';

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

/** POST with cookies; returns [status, body]. */
function post(string $url, array $fields): array
{
    global $cookieJar;
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                  . ($cookieJar !== '' ? "Cookie: {$cookieJar}\r\n" : ''),
        'content' => http_build_query($fields),
        'follow_location' => 0,
        'ignore_errors' => true,
        'timeout' => 10,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#HTTP/\S+\s+(\d+)#', $h, $m)) {
            $status = (int) $m[1];
        }
        if (stripos($h, 'Set-Cookie:') === 0) {
            $parts = explode(';', substr($h, 11));
            $cookieJar = trim($parts[0]);
        }
    }
    return [$status, $body === false ? '' : $body];
}

function get_page(string $url): array
{
    global $cookieJar;
    $ctx = stream_context_create(['http' => [
        'header' => $cookieJar !== '' ? "Cookie: {$cookieJar}\r\n" : '',
        'follow_location' => 0,
        'ignore_errors' => true,
        'timeout' => 10,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#HTTP/\S+\s+(\d+)#', $h, $m)) {
            $status = (int) $m[1];
        }
        if (stripos($h, 'Set-Cookie:') === 0) {
            $parts = explode(';', substr($h, 11));
            $cookieJar = trim($parts[0]);
        }
    }
    return [$status, $body === false ? '' : $body];
}

// Fresh session: fetch the login page and extract the CSRF token.
$res = get_page($base . '/login');
check('login page loads', $res[0] === 200, "got={$res[0]}");
preg_match('/name="_csrf" value="([0-9a-f]+)"/', $res[1], $m);
$token = $m[1] ?? '';
check('CSRF token extracted from page', $token !== '');

// Login without a CSRF token must be rejected with 419.
$res = post($base . '/login', ['email' => 'x@example.com', 'password' => 'whatever']);
check('missing CSRF token rejected (419)', $res[0] === 419, "got={$res[0]}");

// Rate limit: MAX_ATTEMPTS=5 per IP+email within 300s. Use a unique email
// so we always start with a clean counter.
$email = 'brute-' . bin2hex(random_bytes(5)) . '@invalid.test';
$blockedStatus = null;
for ($i = 1; $i <= 6; $i++) {
    // Note: no DB here, so invalid credentials normally fail at User::findByEmail
    // (PDO exception -> 500). The RATE LIMITER runs BEFORE that, so attempt 6
    // must produce a redirect (throttled) instead of reaching the DB.
    $res = post($base . '/login', ['_csrf' => $token, 'email' => $email, 'password' => 'pw' . $i]);
    if ($i === 6) {
        $blockedStatus = $res[0];
    }
}
check('6th attempt throttled to redirect (rate limiter active)', $blockedStatus === 302, "got={$blockedStatus}");

echo "\n" . ($failed === 0 ? "ALL PASSED ({$passed})" : "{$failed} FAILED, {$passed} passed") . "\n";
exit($failed === 0 ? 0 : 1);
