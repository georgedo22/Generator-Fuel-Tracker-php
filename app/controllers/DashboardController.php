<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\FuelLog;
use App\Models\Generator;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $generatorCount = count(Generator::all());
        $logs = FuelLog::allWithGenerator();

        $totalLiters = 0.0;
        $totalCost = 0.0;
        $consumptionSum = 0.0;
        $consumptionCount = 0;

        $monthly = [];
        $perGenerator = [];

        foreach ($logs as $log) {
            $liters = (float) $log['liters'];
            $cost = (float) $log['cost'];
            $totalLiters += $liters;
            $totalCost += $cost;

            if ($log['consumption_per_hour'] !== null) {
                $consumptionSum += (float) $log['consumption_per_hour'];
                $consumptionCount++;
            }

            $monthKey = substr((string) $log['date'], 0, 7);
            if (!isset($monthly[$monthKey])) {
                $monthly[$monthKey] = ['liters' => 0.0, 'cost' => 0.0, 'sum' => 0.0, 'count' => 0];
            }
            $monthly[$monthKey]['liters'] += $liters;
            $monthly[$monthKey]['cost'] += $cost;
            if ($log['consumption_per_hour'] !== null) {
                $monthly[$monthKey]['sum'] += (float) $log['consumption_per_hour'];
                $monthly[$monthKey]['count']++;
            }

            $genId = (string) $log['generator_id'];
            if (!isset($perGenerator[$genId])) {
                $perGenerator[$genId] = [
                    'code' => (string) $log['generator_code'],
                    'liters' => 0.0,
                    'cost' => 0.0,
                    'sum' => 0.0,
                    'count' => 0,
                ];
            }
            $perGenerator[$genId]['liters'] += $liters;
            $perGenerator[$genId]['cost'] += $cost;
            if ($log['consumption_per_hour'] !== null) {
                $perGenerator[$genId]['sum'] += (float) $log['consumption_per_hour'];
                $perGenerator[$genId]['count']++;
            }
        }

        ksort($monthly);
        $monthlySeries = [];
        foreach (array_slice($monthly, -12, null, true) as $month => $v) {
            $monthlySeries[] = [
                'month' => $month,
                'liters' => round($v['liters'], 2),
                'cost' => round($v['cost'], 2),
                'avgConsumption' => $v['count'] > 0 ? round($v['sum'] / $v['count'], 3) : 0,
            ];
        }

        $generatorSummary = [];
        foreach ($perGenerator as $v) {
            $generatorSummary[] = [
                'code' => $v['code'],
                'liters' => round($v['liters'], 2),
                'cost' => round($v['cost'], 2),
                'avgConsumption' => $v['count'] > 0 ? round($v['sum'] / $v['count'], 3) : null,
            ];
        }
        usort($generatorSummary, static fn ($a, $b) => $b['liters'] <=> $a['liters']);

        $recentLogs = array_slice(array_reverse($logs), 0, 8);

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'kpis' => [
                'generatorCount' => $generatorCount,
                'logCount' => count($logs),
                'totalLiters' => round($totalLiters, 2),
                'totalCost' => round($totalCost, 2),
                'avgConsumption' => $consumptionCount > 0 ? round($consumptionSum / $consumptionCount, 3) : null,
            ],
            'monthlySeries' => $monthlySeries,
            'generatorSummary' => $generatorSummary,
            'recentLogs' => $recentLogs,
        ]);
    }
}