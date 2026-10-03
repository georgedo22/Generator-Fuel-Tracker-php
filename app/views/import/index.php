<?php
use App\Core\Helpers;

$actions = '<button type="button" onclick="downloadTemplate()" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">' . icon('download', 'h-4 w-4') . 'Download Template</button>';
?>
<?= page_header('Import Fuel Logs (CSV)', 'Bulk import fuel records. Generators must exist before import.', $actions) ?>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Upload file</h3></div>
        <div class="p-5">
            <form method="post" action="<?= url('/import') ?>" enctype="multipart/form-data" class="space-y-4">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="mode" value="preview">
                <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center hover:border-brand-400">
                    <span class="mb-2 text-slate-400"><?= icon('upload', 'h-6 w-6') ?></span>
                    <span class="text-sm font-medium text-slate-700">Choose a CSV file</span>
                    <input type="file" name="file" accept=".csv,text/csv" required class="mt-2 block w-full text-xs text-slate-500">
                </label>
                <p class="text-xs text-slate-500">
                    Columns: Generator Code, Date, Time, Hour Reading, Liters, Price Per Liter, Fuel Type, Tank Full
                </p>
                <button type="submit" class="h-10 w-full rounded-lg bg-brand-600 text-sm font-medium text-white hover:bg-brand-700">Validate &amp; Preview</button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
        Choose a CSV file and click “Validate &amp; Preview” to review the rows before importing.
    </div>
</div>

<script>
    function downloadTemplate() {
        const headers = ['Generator Code', 'Date', 'Time', 'Hour Reading', 'Liters', 'Price Per Liter', 'Fuel Type', 'Tank Full'];
        const rows = [
            ['GEN-001', '2026-01-05', '08:30', '1000', '0', '0', 'DIESEL', 'Yes'],
            ['GEN-001', '2026-01-06', '09:15', '1050', '20', '850', 'DIESEL', 'No'],
            ['GEN-001', '2026-01-07', '08:00', '1100', '30', '850', 'DIESEL', 'Yes']
        ];
        const csv = '\uFEFF' + [headers, ...rows].map(r => r.join(',')).join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'fuel-logs-template.csv';
        a.click();
        URL.revokeObjectURL(url);
    }
</script>