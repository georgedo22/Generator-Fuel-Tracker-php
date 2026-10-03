<?php
/** @var array $generator */
/** @var array $logs */
/** @var float $totalLiters */
/** @var float $totalCost */
/** @var float|null $avgConsumption */
/** @var array $chart */

use App\Core\Helpers;

$chartJson = json_encode($chart, JSON_UNESCAPED_UNICODE);
?>
<a href="<?= url('/generators') ?>" class="mb-4 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 no-print">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back to generators
</a>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between no-print">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900"><?= Helpers::e((string) $generator['code']) ?></h1>
        <p class="mt-1 flex items-center gap-1 text-sm text-slate-500"><?= icon('map', 'h-4 w-4') ?><?= Helpers::e((string) $generator['location']) ?></p>
    </div>
    <form method="post" action="<?= url('/generators/' . $generator['id'] . '/recompute') ?>">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <?= icon('refresh', 'h-4 w-4') ?> Recalculate
        </button>
    </form>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="h-56 w-full overflow-hidden rounded-t-xl bg-slate-100">
            <?php if (!empty($generator['image_url'])): ?>
                <img src="<?= Helpers::e(Helpers::imageUrl((string) $generator['image_url'])) ?>" alt="<?= Helpers::e((string) $generator['code']) ?>" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full items-center justify-center text-sm text-slate-400">No image</div>
            <?php endif; ?>
        </div>
        <div class="space-y-3 p-5">
            <div>
                <p class="text-xs font-medium uppercase text-slate-500">Location</p>
                <p class="flex items-center gap-1 text-sm text-slate-800"><?= icon('map', 'h-4 w-4') ?><?= Helpers::e((string) $generator['location']) ?></p>
            </div>
            <?php if (!empty($generator['notes'])): ?>
                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Notes</p>
                    <p class="text-sm text-slate-700"><?= Helpers::e((string) $generator['notes']) ?></p>
                </div>
            <?php endif; ?>
            <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-center">
                <div>
                    <p class="text-lg font-semibold text-slate-900"><?= count($logs) ?></p>
                    <p class="text-xs text-slate-500">Logs</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-slate-900"><?= Helpers::e(Helpers::number($totalLiters)) ?></p>
                    <p class="text-xs text-slate-500">Liters</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-slate-900"><?= $avgConsumption !== null ? Helpers::e(Helpers::number($avgConsumption, 2)) : '—' ?></p>
                    <p class="text-xs text-slate-500">Avg L/h</p>
                </div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Fuel Consumption Over Time (L/h)</h3></div>
        <div class="p-5">
            <?php if ($chart === []): ?>
                <div class="flex h-56 items-center justify-center text-sm text-slate-400">Not enough data yet (needs two full-tank logs)</div>
            <?php else: ?>
                <div class="h-56"><canvas id="generatorChart"></canvas></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Fuel Logs</h3></div>
    <div class="table-wrap">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Time</th>
                    <th class="px-5 py-3">Hour Reading</th>
                    <th class="px-5 py-3">Liters</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Tank</th>
                    <th class="px-5 py-3">Hours Worked</th>
                    <th class="px-5 py-3">L/h</th>
                    <th class="px-5 py-3">Cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($logs === []): ?>
                    <tr><td colspan="9" class="px-5 py-8 text-center text-slate-500">No fuel logs for this generator yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="px-5 py-3 text-slate-700"><?= Helpers::e(Helpers::date((string) $l['date'])) ?></td>
                            <td class="px-5 py-3 text-slate-600"><?= Helpers::e((string) $l['time']) ?></td>
                            <td class="px-5 py-3"><?= Helpers::e(Helpers::number($l['hour_reading'])) ?></td>
                            <td class="px-5 py-3"><?= Helpers::e(Helpers::number($l['liters'])) ?></td>
                            <td class="px-5 py-3"><?= Helpers::e(Helpers::fuelLabel((string) $l['fuel_type'])) ?></td>
                            <td class="px-5 py-3"><?= badge(((int) $l['is_tank_full']) === 1 ? 'Full' : 'Not full', ((int) $l['is_tank_full']) === 1 ? 'green' : 'slate') ?></td>
                            <td class="px-5 py-3"><?= $l['hours_worked'] !== null ? Helpers::e(Helpers::number($l['hours_worked'])) : '—' ?></td>
                            <td class="px-5 py-3 font-medium text-brand-700"><?= $l['consumption_per_hour'] !== null ? Helpers::e(Helpers::number($l['consumption_per_hour'], 3)) : '—' ?></td>
                            <td class="px-5 py-3"><?= Helpers::e(Helpers::naira($l['cost'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if ($logs !== []): ?>
                <tfoot class="bg-slate-50 text-sm font-medium">
                    <tr>
                        <td class="px-5 py-3" colspan="3">Total</td>
                        <td class="px-5 py-3"><?= Helpers::e(Helpers::number($totalLiters)) ?></td>
                        <td class="px-5 py-3" colspan="4"></td>
                        <td class="px-5 py-3"><?= Helpers::e(Helpers::naira($totalCost)) ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<p class="mt-4 text-xs text-slate-400 no-print">
    Consumption is calculated between two full-tank records (full-to-full method).
    <a href="<?= url('/logs') ?>" class="text-brand-600 hover:underline">Add a fuel log</a>
</p>

<?php if ($chart !== []): ?>
<script>
    const data = <?= $chartJson ?>;
    new Chart(document.getElementById('generatorChart'), {
        type: 'line',
        data: {
            labels: data.map(d => d.label),
            datasets: [{
                label: 'L/h',
                data: data.map(d => d.value),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
<?php endif; ?>