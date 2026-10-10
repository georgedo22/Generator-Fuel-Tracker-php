<?php
/**
 * LOCAL configuration template.
 *
 * 1. Copy this file to app/config/config.local.php
 * 2. Fill in the real values for your environment.
 *
 * config.local.php is git-ignored — NEVER commit real credentials.
 * The committed app/config/config.php must only contain safe defaults.
 */

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'your_database_name',
        'user' => 'your_database_user',
        // Prefer setting this via the GT_DB_PASS environment variable
        // instead of storing it in this file.
        'pass' => getenv('GT_DB_PASS') !== false ? (string) getenv('GT_DB_PASS') : 'your_database_password',
        'port' => 3306,
        'charset' => 'utf8mb4',
    ],

    'app' => [
        'name' => 'Generator Fuel Tracker',
        'currency_symbol' => '₦',
        // Must stay false in production: never leak error details.
        'debug' => false,
        'timezone' => 'UTC',
        // Set true only when serving over HTTPS.
        'cookie_secure' => true,
        'session_name' => 'gt_prod_session',
        'max_upload_bytes' => 5 * 1024 * 1024, // 5 MB
        'upload_dir' => PROJECT_ROOT . '/public/uploads',
        'upload_url' => '/uploads',
    ],
];
