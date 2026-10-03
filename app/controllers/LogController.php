<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Models\FuelLog;
use App\Models\Generator;
use App\Services\ConsumptionService;
use App\Services\CsvService;

final class LogController extends Controller
{
    private const EXPORT_HEADERS = [
        'Generator Code', 'Date', 'Time', 'Hour Reading', 'Liters',
        'Price Per Liter', 'Fuel Type', 'Tank Full',
        'Hours Worked', 'Consumption Per Hour', 'Total Cost',
    ];

    public function index(): void
    {
        Auth::requireLogin();

        $filters = $this->filters();
        $logs = FuelLog::filtered($filters);
        $generators = Generator::allSimple();

        $totalLiters = 0.0;
        $totalCost = 0.0;
        foreach ($logs as $log) {
            $totalLiters += (float) $log['liters'];
            $totalCost += (float) $log['cost'];
        }

        $this->render('logs/index', [
            'title' => 'Fuel Logs',
            'logs' => $logs,
            'generators' => $generators,
            'filters' => $filters,
            'totalLiters' => round($totalLiters, 2),
            'totalCost' => round($totalCost, 2),
            'old' => $this->old(),
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        $data = $this->collect();
        if ($data === null) {
            $this->back(url('/logs'));
        }

        FuelLog::create($data);
        ConsumptionService::recomputeGenerator((string) $data['generator_id']);
        Flash::success('Fuel log added successfully.');
        $this->back(url('/logs'));
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        $existing = FuelLog::find($id);
        if (!$existing) {
            Flash::error('Fuel log not found.');
            $this->back(url('/logs'));
        }

        $data = $this->collect();
        if ($data === null) {
            $this->back(url('/logs'));
        }

        FuelLog::update($id, $data);
        ConsumptionService::recomputeGenerator((string) $data['generator_id']);
        if ($existing['generator_id'] !== $data['generator_id']) {
            ConsumptionService::recomputeGenerator((string) $existing['generator_id']);
        }
        Flash::success('Fuel log updated.');
        $this->back(url('/logs'));
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Auth::requireCsrf();

        $existing = FuelLog::find($id);
        if (!$existing) {
            Flash::error('Fuel log not found.');
            $this->back(url('/logs'));
        }

        FuelLog::delete($id);
        ConsumptionService::recomputeGenerator((string) $existing['generator_id']);
        Flash::success('Fuel log deleted.');
        $this->back(url('/logs'));
    }

    public function export(): void
    {
        Auth::requireLogin();

        $logs = FuelLog::filtered($this->filters());

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                'Generator Code' => $log['generator_code'],
                'Date' => $log['date'],
                'Time' => $log['time'],
                'Hour Reading' => $log['hour_reading'],
                'Liters' => $log['liters'],
                'Price Per Liter' => $log['price_per_liter'],
                'Fuel Type' => $log['fuel_type'],
                'Tank Full' => ((int) $log['is_tank_full']) === 1 ? 'Yes' : 'No',
                'Hours Worked' => $log['hours_worked'] ?? '',
                'Consumption Per Hour' => $log['consumption_per_hour'] ?? '',
                'Total Cost' => $log['cost'],
            ];
        }

        $csv = CsvService::toCsv($rows, self::EXPORT_HEADERS);
        Response::download('fuel-logs-' . date('Ymd-His') . '.csv', $csv);
    }

    /**
     * Validate the submitted fuel-log form.
     * @return array<string,mixed>|null null on validation failure
     */
    private function collect(): ?array
    {
        $generatorId = $this->input('generator_id');
        $date = $this->input('date');
        $time = $this->input('time');
        $hourReading = $this->input('hour_reading');
        $liters = $this->input('liters');
        $pricePerLiter = $this->input('price_per_liter');
        $fuelType = strtoupper($this->input('fuel_type'));
        $isTankFull = $this->input('is_tank_full') === '1';

        if ($generatorId === '' || !Generator::find($generatorId)) {
            $this->withOld();
            Flash::error('Please choose a valid generator.');
            return null;
        }
        if (!in_array($fuelType, ['PETROL', 'DIESEL', 'KEROSENE'], true)) {
            $this->withOld();
            Flash::error('Please choose a valid fuel type.');
            return null;
        }
        if (!is_numeric($hourReading) || (float) $hourReading < 0
            || !is_numeric($liters) || (float) $liters < 0
            || !is_numeric($pricePerLiter) || (float) $pricePerLiter < 0) {
            $this->withOld();
            Flash::error('Hour reading, liters and price must be non-negative numbers.');
            return null;
        }
        $dateNormalized = $date;
        try {
            $dateNormalized = CsvService::parseDate($date);
            $timeNormalized = CsvService::parseTime($time);
        } catch (\Throwable $e) {
            $this->withOld();
            Flash::error('Please provide a valid date and time.');
            return null;
        }

        return [
            'generator_id' => $generatorId,
            'date' => $dateNormalized,
            'time' => $timeNormalized,
            'hour_reading' => (float) $hourReading,
            'liters' => (float) $liters,
            'price_per_liter' => (float) $pricePerLiter,
            'fuel_type' => $fuelType,
            'is_tank_full' => $isTankFull,
            'cost' => (float) $liters * (float) $pricePerLiter,
        ];
    }

    /** @return array<string,string> */
    private function filters(): array
    {
        return [
            'generatorId' => $this->query('generator_id'),
            'fuelType' => strtoupper($this->query('fuel_type')),
            'tank' => strtoupper($this->query('tank')),
            'from' => $this->query('from'),
            'to' => $this->query('to'),
        ];
    }
}