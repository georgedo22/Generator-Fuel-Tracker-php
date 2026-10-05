<?php
/** @var string $content */
/** @var array|null $auth */
/** @var array $flash */
/** @var string $currentPath */
/** @var string $csrf */
/** @var string $title */

$nav = [
    ['path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['path' => '/generators', 'label' => 'Generators', 'icon' => 'bolt'],
    ['path' => '/logs', 'label' => 'Fuel Logs', 'icon' => 'fuel'],
    ['path' => '/reports', 'label' => 'Reports', 'icon' => 'chart'],
    ['path' => '/import', 'label' => 'Import CSV', 'icon' => 'upload'],
    ['path' => '/users', 'label' => 'Users', 'icon' => 'users', 'admin' => true],
];

$pageTitle = $title ?? 'Generator Fuel Tracker';
$userName = $auth['name'] ?? '';
$userEmail = $auth['email'] ?? '';
$userRole = $auth['role'] ?? '';
$appName = (string) config('app.name', 'Generator Fuel Tracker');
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \App\Core\Helpers::e($pageTitle) ?> · <?= \App\Core\Helpers::e($appName) ?></title>
    <link rel="stylesheet" href="<?= asset('tailwind.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="<?= asset('app.css') ?>?v=<?= @filemtime(base_path('public/assets/app.css')) ?: time() ?>">
    <style>
        [x-cloak] { display: none !important; }
        .table-wrap { overflow-x: auto; }
        .table-wrap table { min-width: 720px; }
        @media print {
            .no-print { display: none !important; }
            main { padding: 0 !important; }
        }
    </style>
</head>
<body class="h-full bg-slate-100 text-slate-900 antialiased">
<div class="flex h-full overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform border-r border-slate-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0 flex flex-col">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#0B2136] to-[#103458] shadow-sm">
                <svg width="24" height="24" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="20,10 40,20 20,30" fill="#E2E8F0" />
                    <polygon points="40,20 40,40 20,30" fill="#94A3B8" />
                    <polygon points="20,30 40,40 20,50" fill="#E2E8F0" />
                    <polygon points="40,40 40,60 20,50" fill="#94A3B8" />
                    <polygon points="20,50 40,60 20,70" fill="#E2E8F0" />
                    <polygon points="40,60 40,80 20,70" fill="#94A3B8" />
                    <polygon points="20,70 40,80 20,90" fill="#E2E8F0" />
                    <polygon points="80,10 60,20 80,30" fill="#E2E8F0" />
                    <polygon points="60,20 60,40 80,30" fill="#94A3B8" />
                    <polygon points="80,30 60,40 80,50" fill="#E2E8F0" />
                    <polygon points="60,40 60,60 80,50" fill="#94A3B8" />
                    <polygon points="80,50 60,60 80,70" fill="#E2E8F0" />
                    <polygon points="60,60 60,80 80,70" fill="#94A3B8" />
                    <polygon points="80,70 60,80 80,90" fill="#E2E8F0" />
                    <polygon points="40,40 60,40 50,50" fill="#CBD5E1" />
                    <polygon points="40,60 60,60 50,50" fill="#CBD5E1" />
                    <polygon points="40,40 50,50 40,60" fill="#94A3B8" />
                    <polygon points="60,40 50,50 60,60" fill="#94A3B8" />
                </svg>
            </div>
            <div class="leading-tight">
                <p class="text-sm font-semibold text-slate-900">Tracker</p>
                <p class="text-xs text-brand-600 font-medium">Hub</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
            <?php foreach ($nav as $item): ?>
                <?php if (!empty($item['admin']) && $userRole !== 'ADMIN') continue; ?>
                <?php
                    $active = $currentPath === $item['path'] || str_starts_with($currentPath, $item['path'] . '/');
                    $classes = $active
                        ? 'bg-brand-50 text-brand-700'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
                ?>
                <a href="<?= url($item['path']) ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition <?= $classes ?>">
                    <span class="h-5 w-5 shrink-0"><?= icon($item['icon']) ?></span>
                    <?= \App\Core\Helpers::e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="border-t border-slate-100 p-3">
            <div class="mb-2 flex items-center gap-3 rounded-lg px-2 py-1">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-600">
                    <?= \App\Core\Helpers::e(strtoupper(substr($userName, 0, 1))) ?>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-800"><?= \App\Core\Helpers::e($userName) ?></p>
                    <p class="truncate text-xs text-slate-500"><?= \App\Core\Helpers::e($userEmail) ?></p>
                </div>
            </div>
            <form method="post" action="<?= url('/logout') ?>">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-red-50 hover:text-red-600">
                    <span class="h-5 w-5"><?= icon('logout') ?></span>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <!-- Backdrop -->
    <div id="backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Content -->
    <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/80 px-4 py-3 backdrop-blur lg:hidden no-print">
            <button type="button" onclick="toggleSidebar()" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="font-semibold text-slate-900"><?= \App\Core\Helpers::e($appName) ?></span>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <?php foreach (($flash ?? []) as $message): ?>
                <?php
                    $isError = ($message['type'] ?? 'success') === 'error';
                    $tone = $isError ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200';
                ?>
                <div class="mb-4 flex items-start gap-2 rounded-xl border <?= $tone ?> px-4 py-3 text-sm no-print">
                    <span class="mt-0.5 h-4 w-4"><?= icon($isError ? 'alert' : 'check') ?></span>
                    <span><?= \App\Core\Helpers::e((string) ($message['message'] ?? '')) ?></span>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<script src="<?= asset('searchable-select.js') ?>?v=<?= @filemtime(base_path('public/assets/searchable-select.js')) ?: time() ?>"></script>
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('backdrop').classList.toggle('hidden');
    }
</script>
</body>
</html>
