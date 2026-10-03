<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function log(string $message): void
    {
        $logDir = PROJECT_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $user = Auth::user() ? ('User: ' . Auth::user()['email']) : 'Guest';
        $formattedMessage = "[$timestamp] [$user] $message" . PHP_EOL;
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
}
