<?php
/** @var array $generators */
/** @var array $filters */
/** @var array $byGenerator */
/** @var array $byFuelType */
/** @var array $totals */

use App\Core\Helpers;

$exportQuery = http_build_query(array_filter([
    'generator_id' => $filters['generatorId'],
    'fuel_type' => $filters['fuelType'],
    'tank' => $filters['tank'],
    'from' => $filters['from'],
    'to' => $filters['to'],
]));

$actions = '<button type="button" onclick="window.print()" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">' . icon('print', 'h-4 w-4') . 'Print</button>';
$actions .= '<a href="' . url('/reports/export' . ($exportQuery !== '' ? '?' . $exportQuery : '')) . '" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white transition hover:bg-brand-700">' . icon('download', 'h-4 w-4') . 'Export CSV</a>';
?>
<?= page_header('Reports', 'Summaries by generator and fuel type with CSV export.', $actions) ?>

<form method="get" action="<?= url('/reports') ?>" class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm no-print">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Generator</label>
            <select name="generator_id" data-searchable class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">All generators</option>
                <?php foreach ($generators as $g): ?>
                    <option value="<?= Helpers::e((string) $g['id']) ?>" <?= $filters['generatorId'] === $g['id'] ? 'selected' : '' ?>><?= Helpers::e((string) $g['code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Fuel Type</label>
            <select name="fuel_type" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">All types</option>
                <?php foreach (['PETROL', 'DIESEL', 'KEROSENE'] as $t): ?>
                    <option value="<?= $t ?>" <?= $filters['fuelType'] === $t ? 'selected' : '' ?>><?= Helpers::e(Helpers::fuelLabel($t)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">From</label>
            <input type="date" name="from" value="<?= Helpers::e($filters['from']) ?>" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">To</label>
            <div class="flex gap-2">
                <input type="date" name="to" value="<?= Helpers::e($filters['to']) ?>" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50"><?= icon('filter', 'h-4 w-4') ?></button>
            </div>
        </div>
    </div>
</form>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Fuel</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= Helpers::e(Helpers::number($totals['liters'])) ?> L</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Cost</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= Helpers::e(Helpers::naira($totals['cost'])) ?></p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Hours Worked</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= Helpers::e(Helpers::number($totals['hours'])) ?></p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Summary by Generator</h3></div>
        <div class="table-wrap">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Generator</th>
                        <th class="px-5 py-3">Logs</th>
                        <th class="px-5 py-3">Liters</th>
                        <th class="px-5 py-3">Cost</th>
                        <th class="px-5 py-3">Hours</th>
                        <th class="px-5 py-3">Avg L/h</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($byGenerator === []): ?>
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No data for the selected filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($byGenerator as $g): ?>
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-800"><?= Helpers::e((string) $g['code']) ?></td>
                                <td class="px-5 py-3"><?= (int) $g['logs'] ?></td>
                                <td class="px-5 py-3"><?= Helpers::e(Helpers::number($g['liters'])) ?></td>
                                <td class="px-5 py-3"><?= Helpers::e(Helpers::naira($g['cost'])) ?></td>
                                <td class="px-5 py-3"><?= Helpers::e(Helpers::number($g['hours'])) ?></td>
                                <td class="px-5 py-3"><?= $g['avgConsumption'] !== null ? Helpers::e(Helpers::number($g['avgConsumption'], 3)) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Summary by Fuel Type</h3></div>
        <div class="space-y-4 p-5">
            <?php foreach (['PETROL', 'DIESEL', 'KEROSENE'] as $t): ?>
                <?php $e = $byFuelType[$t] ?? ['liters' => 0, 'cost' => 0, 'count' => 0]; ?>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-800"><?= Helpers::e(Helpers::fuelLabel($t)) ?></p>
                        <p class="text-xs text-slate-500"><?= (int) $e['count'] ?> logs</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-slate-800"><?= Helpers::e(Helpers::number($e['liters'])) ?> L</p>
                        <p class="text-xs text-slate-500"><?= Helpers::e(Helpers::naira($e['cost'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>