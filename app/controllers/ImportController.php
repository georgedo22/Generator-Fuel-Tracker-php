<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Session;
use App\Models\FuelLog;
use App\Models\Generator;
use App\Services\ConsumptionService;
use App\Services\CsvService;

final class ImportController extends Controller
{
    private const REQUIRED = [
        'Generator Code', 'Date', 'Time', 'Hour Reading',
        'Liters', 'Price Per Liter', 'Fuel Type', 'Tank Full',
    ];

    public function index(): void
    {
        Auth::requireLogin();

        $this->render('import/index', [
            'title' => 'Import Fuel Logs',
            'generators' => Generator::allSimple(),
        ]);
    }

    public function handle(): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        $mode = $this->input('mode', 'preview');

        if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Flash::error('Please choose a CSV file.');
            $this->back(url('/import'));
        }

        if ($mode === 'commit') {
            $this->commit();
            return;
        }

        $path = $this->storeTempUpload();
        $analysis = $this->analyze($path);
        Session::set('logs_import_tmp', $path);

        $this->render('import/preview', [
            'title' => 'Import Preview',
            'analysis' => $analysis,
        ]);
    }

    private function commit(): void
    {
        $path = Session::get('logs_import_tmp');
        if (!is_string($path) || !is_file($path)) {
            Flash::error('Import session expired. Please upload the file again.');
            $this->back(url('/import'));
        }

        $analysis = $this->analyze($path);
        if ($analysis['errors'] !== []) {
            Flash::error('Please fix the reported row errors before importing.');
            $this->back(url('/import'));
        }
        if ($analysis['valid'] === []) {
            Flash::error('No valid rows to import.');
            $this->back(url('/import'));
        }

        $affected = [];
        foreach ($analysis['valid'] as $row) {
            FuelLog::create($row);
            $affected[$row['generator_id']] = true;
        }
        ConsumptionService::recomputeAll(array_keys($affected));

        @unlink($path);
        Session::forget('logs_import_tmp');

        Flash::success('Imported ' . count($analysis['valid']) . ' fuel logs and recalculated consumption.');
        $this->back(url('/logs'));
    }

    /**
     * @return array{rows:array<int,array<string,mixed>>,valid:array<int,array<string,mixed>>,errors:array<int,array{row:int,message:string}>}
     */
    private function analyze(string $path): array
    {
        $rows = CsvService::parseFile($path);
        $valid = [];
        $errors = [];
        $preview = [];

        // Map generator codes (case-insensitive) to ids.
        $byCode = [];
        foreach (Generator::all() as $g) {
            $byCode[strtolower((string) $g['code'])] = (string) $g['id'];
        }

        foreach ($rows as $index => $raw) {
            $rowNumber = $index + 2;

            foreach (self::REQUIRED as $column) {
                if (!array_key_exists($column, $raw)) {
                    $errors[] = ['row' => $rowNumber, 'message' => "Missing column \"{$column}\""];
                    continue 2;
                }
            }

            try {
                $code = trim((string) $raw['Generator Code']);
                if ($code === '') {
                    throw new \RuntimeException('Missing generator code');
                }
                $generatorId = $byCode[strtolower($code)] ?? null;
                if ($generatorId === null) {
                    throw new \RuntimeException("Unknown generator code \"{$code}\" (create it first)");
                }

                $date = CsvService::parseDate((string) $raw['Date']);
                $time = CsvService::parseTime((string) $raw['Time']);

                $hourReading = $raw['Hour Reading'];
                $liters = $raw['Liters'];
                $pricePerLiter = $raw['Price Per Liter'];
                if (!is_numeric($hourReading) || (float) $hourReading < 0) {
                    throw new \RuntimeException('Invalid hour reading');
                }
                if (!is_numeric($liters) || (float) $liters < 0) {
                    throw new \RuntimeException('Invalid liters value');
                }
                if (!is_numeric($pricePerLiter) || (float) $pricePerLiter < 0) {
                    throw new \RuntimeException('Invalid price per liter');
                }

                $fuelType = CsvService::parseFuelType((string) $raw['Fuel Type']);
                $isTankFull = CsvService::parseBool((string) $raw['Tank Full']);

                $valid[] = [
                    'generator_id' => $generatorId,
                    'date' => $date,
                    'time' => $time,
                    'hour_reading' => (float) $hourReading,
                    'liters' => (float) $liters,
                    'price_per_liter' => (float) $pricePerLiter,
                    'fuel_type' => $fuelType,
                    'is_tank_full' => $isTankFull,
                    'cost' => (float) $liters * (float) $pricePerLiter,
                ];

                if (count($preview) < 100) {
                    $preview[] = [
                        'row' => $rowNumber,
                        'generator' => $code,
                        'date' => $date,
                        'time' => $time,
                        'hour_reading' => (float) $hourReading,
                        'liters' => (float) $liters,
                        'price_per_liter' => (float) $pricePerLiter,
                        'fuel_type' => $fuelType,
                        'is_tank_full' => $isTankFull,
                    ];
                }
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
            }
        }

        return ['rows' => $preview, 'valid' => $valid, 'errors' => $errors];
    }

    private function storeTempUpload(): string
    {
        $file = $_FILES['file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Flash::error('CSV upload failed.');
            $this->back(url('/import'));
        }
        $dir = PROJECT_ROOT . '/storage/tmp';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir . '/log-import-' . bin2hex(random_bytes(8)) . '.csv';
        if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
            Flash::error('Unable to store the uploaded file.');
            $this->back(url('/import'));
        }
        return $path;
    }
}