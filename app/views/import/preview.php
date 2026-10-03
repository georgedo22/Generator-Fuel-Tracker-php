<?php
/** @var array $analysis */

use App\Core\Helpers;

$preview = $analysis['rows'];
$errors = $analysis['errors'];
$validCount = count($analysis['valid']);
?>
<?= page_header('Import Preview', 'Review the rows before importing.') ?>

<div class="mb-6 flex flex-wrap gap-2">
    <?= badge($validCount . ' valid', 'green') ?>
    <?= badge(count($errors) . ' errors', $errors ? 'red' : 'slate') ?>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Valid rows preview</h3></div>
        <div class="table-wrap">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-left uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Row</th>
                        <th class="px-3 py-2">Generator</th>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Time</th>
                        <th class="px-3 py-2">Hour</th>
                        <th class="px-3 py-2">Liters</th>
                        <th class="px-3 py-2">₦/L</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Tank</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($preview === []): ?>
                        <tr><td colspan="9" class="px-3 py-8 text-center text-slate-500">No valid rows.</td></tr>
                    <?php else: ?>
                        <?php foreach ($preview as $r): ?>
                            <tr>
                                <td class="px-3 py-2 text-slate-400"><?= (int) $r['row'] ?></td>
                                <td class="px-3 py-2 font-medium"><?= Helpers::e((string) $r['generator']) ?></td>
                                <td class="px-3 py-2"><?= Helpers::e((string) $r['date']) ?></td>
                                <td class="px-3 py-2"><?= Helpers::e((string) $r['time']) ?></td>
                                <td class="px-3 py-2"><?= Helpers::e(Helpers::number($r['hour_reading'])) ?></td>
                                <td class="px-3 py-2"><?= Helpers::e(Helpers::number($r['liters'])) ?></td>
                                <td class="px-3 py-2"><?= Helpers::e(Helpers::number($r['price_per_liter'])) ?></td>
                                <td class="px-3 py-2"><?= Helpers::e(Helpers::fuelLabel((string) $r['fuel_type'])) ?></td>
                                <td class="px-3 py-2"><?= $r['is_tank_full'] ? 'Full' : 'Not full' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($validCount > count($preview)): ?>
            <p class="px-3 py-3 text-xs text-slate-500">Showing the first <?= count($preview) ?> of <?= $validCount ?> valid rows.</p>
        <?php endif; ?>
    </div>

    <div class="space-y-6">
        <?php if ($errors !== []): ?>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="mb-2 flex items-center gap-2 text-sm font-medium text-red-700"><?= icon('alert', 'h-4 w-4') ?> Row errors</p>
                <ul class="max-h-56 space-y-1 overflow-y-auto text-xs text-red-600">
                    <?php foreach ($errors as $e): ?>
                        <li>Row <?= (int) $e['row'] ?>: <?= Helpers::e($e['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-2 text-xs text-red-600">Fix the file and upload again to import.</p>
            </div>
        <?php endif; ?>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="post" action="<?= url('/import') ?>">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="mode" value="commit">
                <button type="submit" <?= ($errors !== [] || $validCount === 0) ? 'disabled' : '' ?>
                        class="h-10 w-full rounded-lg bg-brand-600 text-sm font-medium text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Import <?= $validCount ?> record(s)
                </button>
            </form>
            <a href="<?= url('/import') ?>" class="mt-3 block text-center text-sm text-slate-500 hover:text-slate-800">Cancel</a>
        </div>
    </div>
</div>