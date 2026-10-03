<?php $pageTitle = $title ?? 'Not Found'; ?>
<div class="flex min-h-screen items-center justify-center p-6">
    <div class="text-center">
        <p class="text-6xl font-bold text-brand-600">404</p>
        <h1 class="mt-3 text-2xl font-semibold text-slate-800">Page not found</h1>
        <p class="mt-2 text-sm text-slate-500">The page you are looking for does not exist.</p>
        <a href="<?= url('/dashboard') ?>" class="mt-6 inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Back to dashboard</a>
    </div>
</div>
