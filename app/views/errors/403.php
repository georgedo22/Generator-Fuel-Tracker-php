<?php $pageTitle = $title ?? 'Forbidden'; ?>
<div class="flex min-h-screen items-center justify-center p-6">
    <div class="text-center">
        <p class="text-6xl font-bold text-amber-500">403</p>
        <h1 class="mt-3 text-2xl font-semibold text-slate-800">Access denied</h1>
        <p class="mt-2 text-sm text-slate-500">You do not have permission to view this page.</p>
        <a href="<?= url('/dashboard') ?>" class="mt-6 inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Back to dashboard</a>
    </div>
</div>
