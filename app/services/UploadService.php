<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

/**
 * Handles generator image uploads.
 */
final class UploadService
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public static function uploadDir(): string
    {
        $dir = config('app.upload_dir');
        if (!$dir) {
            $dir = PROJECT_ROOT . '/public/uploads';
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return rtrim((string) $dir, '/\\');
    }

    /**
     * Store an uploaded file and return its public URL, or null when no file.
     *
     * @param array<string,mixed> $file entry from $_FILES
     */
    public static function store(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Image upload failed (error code ' . $file['error'] . ').');
        }

        $maxBytes = (int) config('app.max_upload_bytes', 5 * 1024 * 1024);
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new \RuntimeException('Image must be smaller than 5MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Only JPEG, PNG, WEBP or GIF images are allowed.');
        }

        $ext = self::ALLOWED[$mime];
        $filename = self::uuid() . '.' . $ext;
        $destination = self::uploadDir() . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new \RuntimeException('Unable to save the uploaded image.');
        }

        $prefix = rtrim((string) config('app.upload_url', '/uploads'), '/');
        return $prefix . '/' . $filename;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}