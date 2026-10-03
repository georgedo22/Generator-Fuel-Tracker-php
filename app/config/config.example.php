<?php
/**
 * Default configuration. Do NOT put real credentials here.
 * Copy this file to config.local.php and fill in your values
 * (config.local.php is git-ignored).
 */

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'generator_tracker',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
        'port' => 3306,
    ],

    'app' => [
        'name' => 'Generator Fuel Tracker',
        'currency_symbol' => '₦',
        'debug' => false,
        'timezone' => 'Africa/Lagos',
        // Directory where uploaded generator images are stored.
        // Absolute path; defaults to <project>/public/uploads when null.
        'upload_dir' => null,
        // Public URL prefix for uploaded images.
        'upload_url' => '/uploads',
        // Max upload size in bytes (5 MB).
        'max_upload_bytes' => 5 * 1024 * 1024,
        // Session cookie name.
        'session_name' => 'gt_session',
        // Set true only when serving over HTTPS.
        'cookie_secure' => false,
    ],
];