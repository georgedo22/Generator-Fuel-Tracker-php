<?php

declare(strict_types=1);

/**
 * Test runner: executes every tests/*_test.php file in a separate PHP
 * process and reports the results. No external dependencies required.
 *
 * Run:  php tests/run_all.php
 */

$root = dirname(__DIR__);
$files = glob(__DIR__ . '/*_test.php');
sort($files);

if ($files === [] || $files === false) {
    fwrite(STDERR, "No test files found in tests/\n");
    exit(1);
}

$failedSuites = [];
echo "Running " . count($files) . " test suite(s)...\n\n";

foreach ($files as $file) {
    $name = basename($file);
    echo "== {$name} ==\n";
    $output = [];
    $code = 0;
    exec(PHP_BINARY . ' ' . escapeshellarg($file) . ' 2>&1', $output, $code);
    foreach ($output as $line) {
        echo $line . "\n";
    }
    if ($code !== 0) {
        $failedSuites[] = $name;
        echo "-- {$name}: FAILED (exit {$code}) --\n";
    } else {
        echo "-- {$name}: OK --\n";
    }
    echo "\n";
}

if ($failedSuites !== []) {
    echo 'FAILED SUITES: ' . implode(', ', $failedSuites) . "\n";
    exit(1);
}
echo "ALL SUITES PASSED\n";
exit(0);
