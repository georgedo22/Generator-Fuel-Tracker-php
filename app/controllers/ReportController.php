<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\FuelLog;
use App\Models\Generator;
use App\Services\CsvService;
use App\Core\Response;

final class ReportController extends Controller
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
        $summary = $this->summaries($logs);

        $this->render('reports/index', [
            'title' => 'Reports',
            'logs' => $logs,
            'generators' => $generators,
            'filters' => $filters,
            'byGenerator' => $summary['byGenerator'],
            'byFuelType' => $summary['byFuelType'],
            'totals' => $summary['totals'],
        ]);
    }

    public function export(): void
    {
        Auth::requireLogin();

        $logs = FuelLog::filtered($this->filters());
        $rows = [];
        foreach (array_reverse($logs) as $log) {
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
        Response::download('report-' . date('Ymd-His') . '.csv', $csv);
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

    /**
     * @param array<int,array<string,mixed>> $logs
     * @return array{byGenerator:array<int,array<string,mixed>>,byFuelType:array<string,array<string,mixed>>,totals:array<string,float>}
     */
    private function summaries(array $logs): array
    {
        $byGenerator = [];
        $byFuelType = [];
        $totalLiters = 0.0;
        $totalCost = 0.0;
        $totalHours = 0.0;

        foreach ($logs as $log) {
            $liters = (float) $log['liters'];
            $cost = (float) $log['cost'];
            $totalLiters += $liters;
            $totalCost += $cost;
            $totalHours += (float) ($log['hours_worked'] ?? 0);

            $code = (string) $log['generator_code'];
            if (!isset($byGenerator[$code])) {
                $byGenerator[$code] = ['code' => $code, 'logs' => 0, 'liters' => 0.0, 'cost' => 0.0, 'hours' => 0.0, 'sum' => 0.0, 'count' => 0];
            }
            $byGenerator[$code]['logs']++;
            $byGenerator[$code]['liters'] += $liters;
            $byGenerator[$code]['cost'] += $cost;
            $byGenerator[$code]['hours'] += (float) ($log['hours_worked'] ?? 0);
            if ($log['consumption_per_hour'] !== null) {
                $byGenerator[$code]['sum'] += (float) $log['consumption_per_hour'];
                $byGenerator[$code]['count']++;
            }

            $type = (string) $log['fuel_type'];
            if (!isset($byFuelType[$type])) {
                $byFuelType[$type] = ['liters' => 0.0, 'cost' => 0.0, 'count' => 0];
            }
            $byFuelType[$type]['liters'] += $liters;
            $byFuelType[$type]['cost'] += $cost;
            $byFuelType[$type]['count']++;
        }

        $generatorRows = [];
        foreach ($byGenerator as $row) {
            $generatorRows[] = [
                'code' => $row['code'],
                'logs' => $row['logs'],
                'liters' => round($row['liters'], 2),
                'cost' => round($row['cost'], 2),
                'hours' => round($row['hours'], 2),
                'avgConsumption' => $row['count'] > 0 ? round($row['sum'] / $row['count'], 3) : null,
            ];
        }
        usort($generatorRows, static fn ($a, $b) => $b['liters'] <=> $a['liters']);

        return [
            'byGenerator' => $generatorRows,
            'byFuelType' => $byFuelType,
            'totals' => [
                'liters' => round($totalLiters, 2),
                'cost' => round($totalCost, 2),
                'hours' => round($totalHours, 2),
            ],
        ];
    }
}