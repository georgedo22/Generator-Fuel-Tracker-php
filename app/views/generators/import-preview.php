<?php
/** @var array $analysis */

use App\Core\Helpers;

$valid = $analysis['valid'];
$errors = $analysis['errors'];
$skipped = $analysis['skipped'];
?>
<?= page_header('Import Generators', 'Review the file before importing.') ?>

<div class="mb-6 flex flex-wrap gap-2">
    <?= badge(count($valid) . ' valid', 'green') ?>
    <?= badge(count($skipped) . ' skipped (duplicates)', 'amber') ?>
    <?= badge(count($errors) . ' errors', $errors ? 'red' : 'slate') ?>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Preview (valid rows)</h3></div>
        <div class="table-wrap">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Generator Code</th>
                        <th class="px-5 py-3">Location</th>
                        <th class="px-5 py-3">Notes</th>
                        <th class="px-5 py-3">Image URL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($valid === []): ?>
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No valid rows to import.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($valid, 0, 100) as $row): ?>
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-800"><?= Helpers::e($row['code']) ?></td>
                                <td class="px-5 py-3 text-slate-600"><?= Helpers::e($row['location']) ?></td>
                                <td class="px-5 py-3 text-slate-500"><?= Helpers::e($row['notes']) ?></td>
                                <td class="px-5 py-3 text-slate-500"><?= Helpers::e($row['image_url']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($valid) > 100): ?>
            <p class="px-5 py-3 text-xs text-slate-500">Showing the first 100 of <?= count($valid) ?> valid rows.</p>
        <?php endif; ?>
    </div>

    <div class="space-y-6">
        <?php if ($skipped !== []): ?>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="mb-2 flex items-center gap-2 text-sm font-medium text-amber-800"><?= icon('alert', 'h-4 w-4') ?> Duplicates (skipped)</p>
                <ul class="max-h-48 space-y-1 overflow-y-auto text-xs text-amber-700">
                    <?php foreach ($skipped as $s): ?>
                        <li>Row <?= (int) $s['row'] ?>: <?= Helpers::e($s['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="mb-2 flex items-center gap-2 text-sm font-medium text-red-700"><?= icon('alert', 'h-4 w-4') ?> Row errors</p>
                <ul class="max-h-48 space-y-1 overflow-y-auto text-xs text-red-600">
                    <?php foreach ($errors as $e): ?>
                        <li>Row <?= (int) $e['row'] ?>: <?= Helpers::e($e['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-2 text-xs text-red-600">Invalid rows are ignored during import.</p>
            </div>
        <?php endif; ?>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="post" action="<?= url('/generators/import') ?>">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="mode" value="commit">
                <button type="submit" <?= $valid === [] ? 'disabled' : '' ?>
                        class="h-10 w-full rounded-lg bg-brand-600 text-sm font-medium text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Import <?= count($valid) ?> generator(s)
                </button>
            </form>
            <a href="<?= url('/generators') ?>" class="mt-3 block text-center text-sm text-slate-500 hover:text-slate-800">Cancel</a>
        </div>
    </div>
</div>