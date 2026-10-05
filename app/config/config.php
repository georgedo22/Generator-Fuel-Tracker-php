<?php
// app/config/config.php
//
// SAFE DEFAULTS ONLY. This file is committed to the repository and must
// NEVER contain real credentials. Put environment-specific values (real DB
// user/password, debug flags, ...) in app/config/config.local.php, which is
// git-ignored. See app/config/config.local.example.php for a template.

$config = [
    'app' => [
        'name' => 'Generator Fuel Tracker',
        // هام: يجب ضبطه إلى false في بيئة الإنتاج لمنع تسريب أي تفاصيل أخطاء
        'debug' => false,
        'timezone' => 'UTC',
        // تأكد من ضبطه إلى true عند استخدام HTTPS
        'cookie_secure' => true,
        'session_name' => 'gt_prod_session',
        'max_upload_bytes' => 5 * 1024 * 1024, // 5MB
        'upload_dir' => PROJECT_ROOT . '/public/uploads',
        'upload_url' => '/uploads',
    ],
    'db' => [
        'host' => 'localhost',
        'name' => 'generator_tracker',
        'user' => 'root',
        'pass' => '',
        'port' => 3306,
        'charset' => 'utf8mb4',
    ],
];

// Allow credentials to come from the environment (preferred on shared hosting/CI).
if (getenv('GT_DB_HOST') !== false) {
    $config['db']['host'] = (string) getenv('GT_DB_HOST');
}
if (getenv('GT_DB_NAME') !== false) {
    $config['db']['name'] = (string) getenv('GT_DB_NAME');
}
if (getenv('GT_DB_USER') !== false) {
    $config['db']['user'] = (string) getenv('GT_DB_USER');
}
if (getenv('GT_DB_PASS') !== false) {
    $config['db']['pass'] = (string) getenv('GT_DB_PASS');
}

// Merge local overrides (git-ignored; may contain real credentials).
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        foreach ($local as $section => $values) {
            if (is_array($values) && isset($config[$section]) && is_array($config[$section])) {
                $config[$section] = array_replace($config[$section], $values);
            } else {
                $config[$section] = $values;
            }
        }
    }
}

return $config;
