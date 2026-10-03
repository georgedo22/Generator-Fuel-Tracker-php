<?php
// app/config/config.php

return [
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
        'name' => 'u383732150_dbhabibugen',
        'user' => 'u383732150_dbhabibugen',
        'pass' => '1!2@3#4$5%6^Hg',
        'port' => 3306,
        'charset' => 'utf8mb4',
    ],
];