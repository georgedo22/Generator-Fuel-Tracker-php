<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Session;
use App\Models\Generator;
use App\Services\ConsumptionService;
use App\Services\CsvService;
use App\Services\UploadService;

final class GeneratorController extends Controller
{
    private const IMPORT_HEADERS = ['Generator Code', 'Location', 'Notes'];
    private const EXPORT_HEADERS = ['Generator Code', 'Location', 'Notes', 'Image URL', 'Fuel Logs', 'Created At'];

    public function index(): void
    {
        Auth::requireLogin();

        $search = $this->query('q');
        $generators = Generator::all($search !== '' ? $search : null);

        $this->render('generators/index', [
            'title' => 'Generators',
            'generators' => $generators,
            'search' => $search,
            'old' => $this->old(),
        ]);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();

        $generator = Generator::find($id);
        if (!$generator) {
            http_response_code(404);
            echo \App\Core\View::render('errors/404', ['title' => 'Not found'], 'layouts/guest');
            return;
        }

        $logs = \App\Models\FuelLog::byGenerator($id);

        $totalLiters = 0.0;
        $totalCost = 0.0;
        $consumptionSum = 0.0;
        $consumptionCount = 0;
        $chart = [];
        foreach ($logs as $log) {
            $totalLiters += (float) $log['liters'];
            $totalCost += (float) $log['cost'];
            if ($log['consumption_per_hour'] !== null) {
                $consumptionSum += (float) $log['consumption_per_hour'];
                $consumptionCount++;
                $chart[] = [
                    'label' => substr((string) $log['date'], 5) . ' ' . $log['time'],
                    'value' => round((float) $log['consumption_per_hour'], 3),
                ];
            }
        }

        $this->render('generators/show', [
            'title' => (string) $generator['code'],
            'generator' => $generator,
            'logs' => array_reverse($logs),
            'totalLiters' => round($totalLiters, 2),
            'totalCost' => round($totalCost, 2),
            'avgConsumption' => $consumptionCount > 0 ? round($consumptionSum / $consumptionCount, 3) : null,
            'chart' => $chart,
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        $code = $this->input('code');
        $location = $this->input('location');
        $notes = $this->input('notes');

        if ($code === '' || $location === '') {
            $this->withOld();
            Flash::error('Generator number and location are required.');
            $this->back(url('/generators'));
        }

        if (Generator::codeExists($code)) {
            $this->withOld();
            Flash::error('A generator with this number already exists.');
            $this->back(url('/generators'));
        }

        try {
            $imageUrl = isset($_FILES['image']) ? UploadService::store($_FILES['image']) : null;
        } catch (\Throwable $e) {
            $this->withOld();
            Flash::error($e->getMessage());
            $this->back(url('/generators'));
        }

        Generator::create($code, $location, $imageUrl, $notes !== '' ? $notes : null);
        Flash::success('Generator added successfully.');
        $this->back(url('/generators'));
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        $generator = Generator::find($id);
        if (!$generator) {
            Flash::error('Generator not found.');
            $this->back(url('/generators'));
        }

        $code = $this->input('code');
        $location = $this->input('location');
        $notes = $this->input('notes');

        if ($code === '' || $location === '') {
            Flash::error('Generator number and location are required.');
            $this->back(url('/generators'));
        }
        if (Generator::codeExists($code, $id)) {
            Flash::error('A generator with this number already exists.');
            $this->back(url('/generators'));
        }

        $imageUrl = $generator['image_url'] ?: null;

        // Remove existing image requested?
        if ($this->input('remove_image') === '1') {
            $imageUrl = null;
        }

        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $imageUrl = UploadService::store($_FILES['image']);
            } catch (\Throwable $e) {
                Flash::error($e->getMessage());
                $this->back(url('/generators'));
            }
        }

        Generator::update($id, $code, $location, $imageUrl, $notes !== '' ? $notes : null);
        Flash::success('Generator updated successfully.');
        $this->back(url('/generators'));
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        Generator::delete($id);
        Flash::success('Generator deleted. Its fuel logs were removed as well.');
        $this->back(url('/generators'));
    }

    public function recompute(string $id): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        ConsumptionService::recomputeGenerator($id);
        Flash::success('Consumption recalculated.');
        $this->back(url('/generators/' . $id));
    }

    public function export(): void
    {
        Auth::requireLogin();

        $search = $this->query('q');
        $generators = Generator::all($search !== '' ? $search : null);

        $rows = [];
        foreach ($generators as $g) {
            $rows[] = [
                'Generator Code' => $g['code'],
                'Location' => $g['location'],
                'Notes' => $g['notes'] ?? '',
                'Image URL' => $g['image_url'] ?? '',
                'Fuel Logs' => $g['fuel_logs_count'] ?? 0,
                'Created At' => substr((string) $g['created_at'], 0, 10),
            ];
        }

        $csv = CsvService::toCsv($rows, self::EXPORT_HEADERS);
        Response::download('generators-' . date('Ymd-His') . '.csv', $csv);
    }

    /**
     * Generator CSV import: preview then commit.
     * Duplicates are skipped and reported (never updated).
     */
    public function import(): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        $mode = $this->input('mode', 'preview');

        if ($mode === 'commit') {
            $this->commitImport();
            return;
        }

        // Preview mode: validate the uploaded file and show a preview.
        if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Flash::error('Please choose a CSV file.');
            $this->back(url('/generators'));
        }

        $tmpPath = $this->storeTempUpload($_FILES['file']);
        $analysis = $this->analyzeImport($tmpPath);
        Session::set('generators_import_tmp', $tmpPath);

        $generators = Generator::all();
        $this->render('generators/import-preview', [
            'title' => 'Import Generators',
            'analysis' => $analysis,
            'generators' => $generators,
        ]);
    }

    private function commitImport(): void
    {
        $tmpPath = Session::get('generators_import_tmp');
        if (!is_string($tmpPath) || !is_file($tmpPath)) {
            Flash::error('Import session expired. Please upload the file again.');
            $this->back(url('/generators'));
        }

        $analysis = $this->analyzeImport($tmpPath);
        if ($analysis['valid'] === []) {
            Flash::error('No valid rows to import.');
            $this->back(url('/generators'));
        }

        foreach ($analysis['valid'] as $row) {
            Generator::create($row['code'], $row['location'], $row['image_url'], $row['notes'] !== '' ? $row['notes'] : null);
        }

        @unlink($tmpPath);
        Session::forget('generators_import_tmp');

        Flash::success('Imported ' . count($analysis['valid']) . ' generators. Skipped '
            . count($analysis['skipped']) . ' duplicate(s), ' . count($analysis['errors']) . ' invalid row(s).');
        $this->back(url('/generators'));
    }

    /**
     * @return array{valid:array<int,array<string,string>>,errors:array<int,array{row:int,message:string}>,skipped:array<int,array{row:int,message:string}>}
     */
    private function analyzeImport(string $path): array
    {
        $rows = CsvService::parseFile($path);
        $valid = [];
        $errors = [];
        $skipped = [];
        $seenCodes = [];

        // Existing codes (case-insensitive).
        $existing = [];
        foreach (Generator::all() as $g) {
            $existing[strtolower((string) $g['code'])] = true;
        }

        foreach ($rows as $index => $raw) {
            $rowNumber = $index + 2;
            if (!array_key_exists('Generator Code', $raw) || !array_key_exists('Location', $raw)) {
                $errors[] = ['row' => $rowNumber, 'message' => 'Missing required columns "Generator Code"/"Location"'];
                continue;
            }

            $code = trim((string) $raw['Generator Code']);
            $location = trim((string) $raw['Location']);
            $notes = trim((string) ($raw['Notes'] ?? ''));
            $imageUrl = trim((string) ($raw['Image URL'] ?? ''));

            if ($code === '') {
                $errors[] = ['row' => $rowNumber, 'message' => 'Missing generator code'];
                continue;
            }
            if ($location === '') {
                $errors[] = ['row' => $rowNumber, 'message' => 'Missing location'];
                continue;
            }

            $key = strtolower($code);
            if (isset($existing[$key]) || isset($seenCodes[$key])) {
                $skipped[] = ['row' => $rowNumber, 'message' => "Duplicate generator code \"{$code}\" — skipped"];
                continue;
            }
            $seenCodes[$key] = true;

            $valid[] = [
                'code' => $code,
                'location' => $location,
                'notes' => $notes,
                'image_url' => $imageUrl,
            ];
        }

        return ['valid' => $valid, 'errors' => $errors, 'skipped' => $skipped];
    }

    private function storeTempUpload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Flash::error('CSV upload failed.');
            $this->back(url('/generators'));
        }
        $dir = PROJECT_ROOT . '/storage/tmp';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir . '/gen-import-' . bin2hex(random_bytes(8)) . '.csv';
        if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
            Flash::error('Unable to store the uploaded file.');
            $this->back(url('/generators'));
        }
        return $path;
    }
}