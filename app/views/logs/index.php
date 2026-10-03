<?php
/** @var array $logs */
/** @var array $generators */
/** @var array $filters */
/** @var float $totalLiters */
/** @var float $totalCost */

use App\Core\Helpers;

$exportQuery = http_build_query(array_filter([
    'generator_id' => $filters['generatorId'],
    'fuel_type' => $filters['fuelType'],
    'tank' => $filters['tank'],
    'from' => $filters['from'],
    'to' => $filters['to'],
]));

$actions = '<a href="' . url('/logs/export' . ($exportQuery !== '' ? '?' . $exportQuery : '')) . '" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">' . icon('download', 'h-4 w-4') . 'Export CSV</a>';
$actions .= '<button type="button" onclick="openLogCreate()" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white transition hover:bg-brand-700">' . icon('plus', 'h-4 w-4') . 'Add Fuel Log</button>';
?>
<?= page_header('Fuel Logs', 'Record fuel refills and monitor calculated consumption.', $actions) ?>

<form method="get" action="<?= url('/logs') ?>" class="mb-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm no-print">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Generator</label>
            <select name="generator_id" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
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
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Tank</label>
            <select name="tank" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">All</option>
                <option value="FULL" <?= $filters['tank'] === 'FULL' ? 'selected' : '' ?>>Full</option>
                <option value="EMPTY" <?= $filters['tank'] === 'EMPTY' ? 'selected' : '' ?>>Not full</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">From</label>
            <input type="date" name="from" value="<?= Helpers::e($filters['from']) ?>" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <div class="lg:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">To</label>
            <div class="flex min-w-0 gap-2">
                <input type="date" name="to" value="<?= Helpers::e($filters['to']) ?>" class="h-10 min-w-0 flex-1 rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50" title="Apply filters"><?= icon('filter', 'h-4 w-4') ?></button>
                <a href="<?= url('/logs') ?>" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-500 hover:bg-slate-50" title="Clear"><?= icon('refresh', 'h-4 w-4') ?></a>
            </div>
        </div>
    </div>
</form>

<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="table-wrap">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Generator</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Hour Rdg.</th>
                    <th class="px-4 py-3">Liters</th>
                    <th class="px-4 py-3">₦/L</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Tank</th>
                    <th class="px-4 py-3">Hours</th>
                    <th class="px-4 py-3">L/h</th>
                    <th class="px-4 py-3">Cost</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($logs === []): ?>
                    <tr><td colspan="12" class="px-4 py-8 text-center text-slate-500">No fuel logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800"><?= Helpers::e((string) $l['generator_code']) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= Helpers::e(Helpers::date((string) $l['date'])) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= Helpers::e((string) $l['time']) ?></td>
                            <td class="px-4 py-3"><?= Helpers::e(Helpers::number($l['hour_reading'])) ?></td>
                            <td class="px-4 py-3"><?= Helpers::e(Helpers::number($l['liters'])) ?></td>
                            <td class="px-4 py-3"><?= Helpers::e(Helpers::number($l['price_per_liter'])) ?></td>
                            <td class="px-4 py-3"><?= Helpers::e(Helpers::fuelLabel((string) $l['fuel_type'])) ?></td>
                            <td class="px-4 py-3"><?= badge(((int) $l['is_tank_full']) === 1 ? 'Full' : 'Not full', ((int) $l['is_tank_full']) === 1 ? 'green' : 'slate') ?></td>
                            <td class="px-4 py-3"><?= $l['hours_worked'] !== null ? Helpers::e(Helpers::number($l['hours_worked'])) : '—' ?></td>
                            <td class="px-4 py-3 font-medium text-brand-700"><?= $l['consumption_per_hour'] !== null ? Helpers::e(Helpers::number($l['consumption_per_hour'], 3)) : '—' ?></td>
                            <td class="px-4 py-3"><?= Helpers::e(Helpers::naira($l['cost'])) ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <button type="button" title="Edit" onclick='openLogEdit(<?= json_encode($l, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)' class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700"><?= icon('pencil', 'h-4 w-4') ?></button>
                                    <form method="post" action="<?= url('/logs/' . $l['id'] . '/delete') ?>" onsubmit="return confirm('Delete this fuel log? Consumption will be recalculated.');">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" title="Delete" class="flex h-8 w-8 items-center justify-center rounded-lg text-red-600 hover:bg-red-50"><?= icon('trash', 'h-4 w-4') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if ($logs !== []): ?>
                <tfoot class="bg-slate-50 font-medium">
                    <tr>
                        <td class="px-4 py-3" colspan="4">Total (<?= count($logs) ?> logs)</td>
                        <td class="px-4 py-3"><?= Helpers::e(Helpers::number($totalLiters)) ?></td>
                        <td class="px-4 py-3" colspan="5"></td>
                        <td class="px-4 py-3"><?= Helpers::e(Helpers::naira($totalCost)) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Fuel log modal -->
<div id="logModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
    <div class="mx-auto mt-10 w-full max-w-xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 id="logModalTitle" class="text-base font-semibold text-slate-900">Add Fuel Log</h2>
            <button type="button" onclick="closeModal('logModal')" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="logForm" method="post" action="<?= url('/logs') ?>" class="space-y-4 p-5">
            <?= \App\Core\Csrf::field() ?>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Generator</label>
                <select name="generator_id" id="l_generator" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Select a generator</option>
                    <?php foreach ($generators as $g): ?>
                        <option value="<?= Helpers::e((string) $g['id']) ?>"><?= Helpers::e((string) $g['code']) ?> — <?= Helpers::e((string) $g['location']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Date</label>
                    <input type="date" name="date" id="l_date" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Time</label>
                    <input type="time" name="time" id="l_time" required class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Hour Reading (meter)</label>
                    <input type="number" step="any" min="0" name="hour_reading" id="l_hour" required placeholder="e.g. 1250.5" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Fuel Type</label>
                    <select name="fuel_type" id="l_type" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <?php foreach (['PETROL', 'DIESEL', 'KEROSENE'] as $t): ?>
                            <option value="<?= $t ?>" <?= $t === 'DIESEL' ? 'selected' : '' ?>><?= Helpers::e(Helpers::fuelLabel($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Liters</label>
                    <input type="number" step="any" min="0" name="liters" id="l_liters" required placeholder="e.g. 40" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Price per Liter (₦)</label>
                    <input type="number" step="any" min="0" name="price_per_liter" id="l_price" required placeholder="e.g. 850" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_tank_full" id="l_full" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-700">Tank was filled to full</span>
                </label>
                <span class="text-sm text-slate-600">Cost: <strong id="l_cost">0.00</strong></span>
            </div>
            <p class="rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-700">
                Consumption per hour is only calculated when the tank is filled to full, based on the distance from the previous full-tank log.
            </p>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('logModal')" class="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="logSubmit" class="h-10 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700">Add log</button>
            </div>
        </form>
    </div>
</div>

<script>
    const TODAY = <?= json_encode(date('Y-m-d')) ?>;
    const NOW = <?= json_encode(date('H:i')) ?>;
    const BASE = <?= json_encode(url('/logs')) ?>;

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    function recalcCost() {
        const liters = parseFloat(document.getElementById('l_liters').value) || 0;
        const price = parseFloat(document.getElementById('l_price').value) || 0;
        document.getElementById('l_cost').textContent = (liters * price).toFixed(2);
    }
    document.getElementById('l_liters').addEventListener('input', recalcCost);
    document.getElementById('l_price').addEventListener('input', recalcCost);

    function openLogCreate() {
        document.getElementById('logModalTitle').textContent = 'Add Fuel Log';
        document.getElementById('logForm').action = BASE;
        document.getElementById('logSubmit').textContent = 'Add log';
        document.getElementById('l_generator').value = '<?= Helpers::e($filters['generatorId']) ?>' || '';
        document.getElementById('l_date').value = TODAY;
        document.getElementById('l_time').value = NOW;
        document.getElementById('l_hour').value = '';
        document.getElementById('l_type').value = 'DIESEL';
        document.getElementById('l_liters').value = '';
        document.getElementById('l_price').value = '';
        document.getElementById('l_full').checked = false;
        recalcCost();
        openModal('logModal');
    }

    function openLogEdit(l) {
        document.getElementById('logModalTitle').textContent = 'Edit Fuel Log';
        document.getElementById('logForm').action = BASE + '/' + l.id + '/update';
        document.getElementById('logSubmit').textContent = 'Save changes';
        document.getElementById('l_generator').value = l.generator_id;
        document.getElementById('l_date').value = (l.date || '').substring(0, 10);
        document.getElementById('l_time').value = (l.time || '').substring(0, 5);
        document.getElementById('l_hour').value = l.hour_reading;
        document.getElementById('l_type').value = l.fuel_type;
        document.getElementById('l_liters').value = l.liters;
        document.getElementById('l_price').value = l.price_per_liter;
        document.getElementById('l_full').checked = parseInt(l.is_tank_full, 10) === 1;
        recalcCost();
        openModal('logModal');
    }

    document.querySelectorAll('.fixed.inset-0').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.add('hidden');
        });
    });
</script>