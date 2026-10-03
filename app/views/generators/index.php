<?php
/** @var array $generators */
/** @var string $search */
/** @var array $old */

use App\Core\Helpers;

$isAdmin = ($auth['role'] ?? '') === 'ADMIN';
// The header actions.
$actions = '<a href="' . url('/generators/export' . ($search !== '' ? '?q=' . urlencode($search) : '')) . '" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">' . icon('download', 'h-4 w-4') . 'Export CSV</a>';
$actions .= '<button type="button" onclick="openImport()" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">' . icon('upload', 'h-4 w-4') . 'Import CSV</button>';
$actions .= '<button type="button" onclick="openCreate()" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white transition hover:bg-brand-700">' . icon('plus', 'h-4 w-4') . 'Add Generator</button>';
?>
<?= page_header('Generators', 'Register generators with their number, location and photo.', $actions) ?>

<form method="get" action="<?= url('/generators') ?>" class="mb-4 max-w-sm no-print">
    <div class="relative">
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><?= icon('search', 'h-4 w-4') ?></span>
        <input type="text" name="q" value="<?= Helpers::e($search) ?>" placeholder="Search by number or location..."
               class="h-10 w-full rounded-lg border border-slate-300 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500">
    </div>
</form>

<?php if ($generators === []): ?>
    <div class="rounded-xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500">
        No generators yet. Click “Add Generator” to create the first one.
    </div>
<?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($generators as $g): ?>
            <div class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="h-40 w-full bg-slate-100">
                    <?php if (!empty($g['image_url'])): ?>
                        <img src="<?= Helpers::e(Helpers::imageUrl((string) $g['image_url'])) ?>" alt="<?= Helpers::e((string) $g['code']) ?>" class="h-full w-full object-cover">
                    <?php else: ?>
                        <div class="flex h-full items-center justify-center text-sm text-slate-400">No image</div>
                    <?php endif; ?>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900"><?= Helpers::e((string) $g['code']) ?></p>
                            <p class="flex items-center gap-1 truncate text-sm text-slate-500"><?= icon('map', 'h-3.5 w-3.5') ?><?= Helpers::e((string) $g['location']) ?></p>
                        </div>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600"><?= (int) ($g['fuel_logs_count'] ?? 0) ?> logs</span>
                    </div>
                    <?php if (!empty($g['notes'])): ?>
                        <p class="mb-3 line-clamp-2 text-sm text-slate-500"><?= Helpers::e((string) $g['notes']) ?></p>
                    <?php endif; ?>

                    <div class="mt-auto flex items-center gap-2">
                        <a href="<?= url('/generators/' . $g['id']) ?>" class="flex-1">
                            <span class="flex h-9 w-full items-center justify-center rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 transition hover:bg-slate-50">View details</span>
                        </a>
                        <button type="button" title="Edit" onclick='openEdit(<?= json_encode($g, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                            <?= icon('pencil', 'h-4 w-4') ?>
                        </button>
                        <form method="post" action="<?= url('/generators/' . $g['id'] . '/delete') ?>" onsubmit="return confirm('Delete this generator and all of its fuel logs?');">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" title="Delete" class="flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50">
                                <?= icon('trash', 'h-4 w-4') ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add / Edit generator modal -->
<div id="generatorModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
    <div class="mx-auto mt-10 w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 id="generatorModalTitle" class="text-base font-semibold text-slate-900">Add Generator</h2>
            <button type="button" onclick="closeModal('generatorModal')" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="generatorForm" method="post" action="<?= url('/generators') ?>" enctype="multipart/form-data" class="space-y-4 p-5">
            <?= \App\Core\Csrf::field() ?>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Generator Number / Code</label>
                <input type="text" name="code" id="g_code" required placeholder="e.g. GEN-001"
                       class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
                <input type="text" name="location" id="g_location" required placeholder="e.g. Main Site - Building A"
                       class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Photo</label>
                <div class="flex items-center gap-4">
                    <div id="g_preview" class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                        <?= icon('bolt', 'h-6 w-6') ?>
                    </div>
                    <div class="space-y-2">
                        <input type="file" name="image" accept="image/*" class="block text-sm text-slate-600">
                        <label class="flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="remove_image" id="g_remove" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Remove current image
                        </label>
                    </div>
                </div>
                <p class="mt-1 text-xs text-slate-500">JPEG, PNG, WEBP or GIF. Max 5MB.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Notes (optional)</label>
                <textarea name="notes" id="g_notes" rows="3" placeholder="Capacity, model, remarks..."
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('generatorModal')" class="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="generatorSubmit" class="h-10 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700">Add generator</button>
            </div>
        </form>
    </div>
</div>

<!-- Import CSV modal -->
<div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
    <div class="mx-auto mt-10 w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Import Generators (CSV)</h2>
            <button type="button" onclick="closeModal('importModal')" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="post" action="<?= url('/generators/import') ?>" enctype="multipart/form-data" class="space-y-4 p-5">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="mode" value="preview">
            <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                Columns: <strong>Generator Code, Location, Notes</strong> (optional: Image URL).<br>
                Duplicate generator codes are <strong>skipped and reported</strong>.
            </div>
            <input type="file" name="file" accept=".csv,text/csv" required class="block w-full text-sm text-slate-600">
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('importModal')" class="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700">Validate &amp; Preview</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    function openCreate() {
        document.getElementById('generatorModalTitle').textContent = 'Add Generator';
        document.getElementById('generatorForm').action = <?= json_encode(url('/generators')) ?>;
        document.getElementById('generatorSubmit').textContent = 'Add generator';
        document.getElementById('g_code').value = '';
        document.getElementById('g_location').value = '';
        document.getElementById('g_notes').value = '';
        document.getElementById('g_remove').checked = false;
        document.getElementById('g_remove').closest('label').classList.add('hidden');
        setPreview('');
        openModal('generatorModal');
    }

    function openEdit(g) {
        document.getElementById('generatorModalTitle').textContent = 'Edit Generator';
        document.getElementById('generatorForm').action = <?= json_encode(url('/generators')) ?> + '/' + g.id + '/update';
        document.getElementById('generatorSubmit').textContent = 'Save changes';
        document.getElementById('g_code').value = g.code || '';
        document.getElementById('g_location').value = g.location || '';
        document.getElementById('g_notes').value = g.notes || '';
        document.getElementById('g_remove').checked = false;
        document.getElementById('g_remove').closest('label').classList.toggle('hidden', !g.image_url);
        setPreview(g.image_url ? buildUrl(g.image_url) : '');
        openModal('generatorModal');
    }

    function openImport() { openModal('importModal'); }

    function buildUrl(u) {
        if (/^https?:\/\//i.test(u)) return u;
        return <?= json_encode(APP_BASE) ?> + (u.startsWith('/') ? u : '/' + u);
    }

    function setPreview(url) {
        const box = document.getElementById('g_preview');
        if (url) {
            box.innerHTML = '<img src="' + url + '" alt="" class="h-full w-full object-cover">';
        } else {
            box.innerHTML = <?= json_encode(icon('bolt', 'h-6 w-6')) ?>;
        }
    }

    // Close modal on backdrop click.
    document.querySelectorAll('.fixed.inset-0').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.add('hidden');
        });
    });
</script>