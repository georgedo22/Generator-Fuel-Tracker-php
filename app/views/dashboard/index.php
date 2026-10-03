<?php
/** @var array $kpis */
/** @var array $monthlySeries */
/** @var array $generatorSummary */
/** @var array $recentLogs */

use App\Core\Helpers;

$monthlyJson = json_encode($monthlySeries, JSON_UNESCAPED_UNICODE);
?>
<?= page_header('Dashboard', 'Overview of generators, fuel usage and consumption.') ?>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php
    $cards = [
        ['Generators', (string) $kpis['generatorCount'], 'bolt', 'bg-brand-100 text-brand-600'],
        ['Total Fuel (L)', Helpers::number($kpis['totalLiters']), 'fuel', 'bg-emerald-100 text-emerald-600'],
        ['Total Cost', Helpers::naira($kpis['totalCost']), 'chart', 'bg-amber-100 text-amber-600'],
        ['Avg Consumption (L/h)', $kpis['avgConsumption'] !== null ? Helpers::number($kpis['avgConsumption'], 3) : '—', 'grid', 'bg-purple-100 text-purple-600'],
    ];
    ?>
    <?php foreach ($cards as [$label, $value, $ic, $tone]): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg <?= $tone ?>"><?= icon($ic) ?></div>
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500"><?= Helpers::e($label) ?></p>
                    <p class="truncate text-xl font-semibold text-slate-900"><?= Helpers::e($value) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Fuel Used per Month (Liters)</h3></div>
        <div class="p-5">
            <?php if ($monthlySeries === []): ?>
                <p class="py-16 text-center text-sm text-slate-400">No data to display yet</p>
            <?php else: ?>
                <div class="h-64"><canvas id="litersChart"></canvas></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Average Consumption per Month (L/h)</h3></div>
        <div class="p-5">
            <?php if ($monthlySeries === []): ?>
                <p class="py-16 text-center text-sm text-slate-400">No data to display yet</p>
            <?php else: ?>
                <div class="h-64"><canvas id="consumptionChart"></canvas></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Consumption by Generator</h3></div>
        <div class="table-wrap">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Generator</th>
                        <th class="px-5 py-3">Liters</th>
                        <th class="px-5 py-3">Cost</th>
                        <th class="px-5 py-3">Avg L/h</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($generatorSummary === []): ?>
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">No data yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($generatorSummary as $g): ?>
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-800"><?= Helpers::e((string) $g['code']) ?></td>
                                <td class="px-5 py-3"><?= Helpers::e(Helpers::number($g['liters'])) ?></td>
                                <td class="px-5 py-3"><?= Helpers::e(Helpers::naira($g['cost'])) ?></td>
                                <td class="px-5 py-3"><?= $g['avgConsumption'] !== null ? Helpers::e(Helpers::number($g['avgConsumption'], 3)) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Recent Fuel Logs</h3></div>
        <div class="table-wrap">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Generator</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Liters</th>
                        <th class="px-5 py-3">Tank</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($recentLogs === []): ?>
                        <tr><td colspan="5" class="px-5 py-6 text-center text-slate-500">No logs yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $l): ?>
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-800"><?= Helpers::e((string) $l['generator_code']) ?></td>
                                <td class="px-5 py-3 text-slate-600"><?= Helpers::e(Helpers::date((string) $l['date'])) ?></td>
                                <td class="px-5 py-3 text-slate-600"><?= Helpers::e(Helpers::fuelLabel((string) $l['fuel_type'])) ?></td>
                                <td class="px-5 py-3"><?= Helpers::e(Helpers::number($l['liters'])) ?></td>
                                <td class="px-5 py-3"><?= badge(((int) $l['is_tank_full']) === 1 ? 'Full' : 'Not full', ((int) $l['is_tank_full']) === 1 ? 'green' : 'slate') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($monthlySeries !== []): ?>
<script>
    const monthly = <?= $monthlyJson ?>;
    const labels = monthly.map(m => m.month);
    const currency = <?= json_encode((string) config('app.currency_symbol', '₦')) ?>;

    new Chart(document.getElementById('litersChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Liters',
                data: monthly.map(m => m.liters),
                backgroundColor: '#2563eb',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('consumptionChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'L/h',
                data: monthly.map(m => m.avgConsumption),
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
<?php endif; ?>