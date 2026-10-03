<?php

declare(strict_types=1);

namespace App\Services;

/**
 * CSV parsing and generation helpers.
 */
final class CsvService
{
    /**
     * Convert rows to a CSV string with a UTF-8 BOM (Excel friendly).
     *
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,string> $headers
     */
    public static function toCsv(array $rows, array $headers): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                }
                $line[] = $value;
            }
            fputcsv($handle, $line);
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    /**
     * Parse a CSV file into associative rows keyed by header.
     *
     * @return array<int,array<string,string>>
     */
    public static function parseFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read the CSV file.');
        }

        $headers = null;
        $rows = [];
        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($headers === null) {
                // Strip a possible BOM from the first header cell.
                $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($line[0] ?? ''));
                $headers = array_map(static fn ($h) => trim((string) $h), $line);
                continue;
            }
            if (count($line) === 1 && trim((string) $line[0]) === '') {
                continue; // skip blank lines
            }
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = trim((string) ($line[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    public static function parseBool(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['true', '1', 'yes', 'y', 'full'], true);
    }

    public static function parseFuelType(string $value): string
    {
        $v = strtoupper(trim($value));
        return match (true) {
            in_array($v, ['PETROL', 'GASOLINE', 'BENZIN'], true) => 'PETROL',
            in_array($v, ['DIESEL', 'MAZOT', 'GASOIL'], true) => 'DIESEL',
            in_array($v, ['KEROSENE', 'KEROSIN'], true) => 'KEROSENE',
            default => throw new \RuntimeException("Unknown fuel type: {$value}"),
        };
    }

    /** Normalizes dates: 2026-01-05, 05/01/2026 or 01/05/2026 (day/month). */
    public static function parseDate(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            throw new \RuntimeException('Missing date');
        }

        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        if (preg_match('#^(\d{1,2})[/.](\d{1,2})[/.](\d{4})$#', $v, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $day = $a > 12 ? $a : ($b > 12 ? $b : $a);
            $month = $a > 12 ? $b : ($b > 12 ? $a : $b);
            return sprintf('%04d-%02d-%02d', $m[3], $month, $day);
        }

        $ts = strtotime($v);
        if ($ts === false) {
            throw new \RuntimeException("Invalid date: {$value}");
        }
        return date('Y-m-d', $ts);
    }

    /** Normalizes times to HH:MM. */
    public static function parseTime(string $value): string
    {
        $v = trim($value);
        if (preg_match('#^(\d{1,2}):(\d{2})#', $v, $m)) {
            return sprintf('%02d:%02d', $m[1], $m[2]);
        }
        $ts = strtotime($v);
        if ($ts === false) {
            throw new \RuntimeException("Invalid time: {$value}");
        }
        return date('H:i', $ts);
    }
}