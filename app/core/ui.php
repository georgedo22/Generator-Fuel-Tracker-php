<?php

declare(strict_types=1);

/**
 * UI helper functions available to all views and layouts.
 * Required from bootstrap before routing.
 */

if (!function_exists('icon')) {
    /** Inline SVG icon set. */
    function icon(string $name, string $class = 'h-5 w-5'): string
    {
        $paths = [
            'grid' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>',
            'bolt' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h7l-1 8 10-12h-7z"/>',
            'fuel' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 22h12V4a2 2 0 00-2-2H5a2 2 0 00-2 2v18zM3 9h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 8h2a2 2 0 012 2v8a2 2 0 004 0V9l-3-3"/>',
            'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'upload' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
            'download' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
            'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>',
            'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/>',
            'alert' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>',
            'plus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>',
            'pencil' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4z"/>',
            'trash' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/>',
            'map' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
            'search' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>',
            'refresh' => '<path stroke-linecap="round" stroke-linejoin="round" d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>',
            'print' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>',
            'file' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M9 13h6M9 17h6"/>',
            'filter' => '<path stroke-linecap="round" stroke-linejoin="round" d="M22 3H2l8 9.46V19l4 2v-8.54z"/>',
        ];
        $path = $paths[$name] ?? '';
        return '<svg class="' . $class . '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' . $path . '</svg>';
    }
}

if (!function_exists('page_header')) {
    /** Render a page header with optional action buttons (raw HTML). */
    function page_header(string $title, string $description = '', string $actions = ''): string
    {
        $html = '<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between no-print">';
        $html .= '<div><h1 class="text-2xl font-semibold text-slate-900">' . \App\Core\Helpers::e($title) . '</h1>';
        if ($description !== '') {
            $html .= '<p class="mt-1 text-sm text-slate-500">' . \App\Core\Helpers::e($description) . '</p>';
        }
        $html .= '</div>';
        if ($actions !== '') {
            $html .= '<div class="flex flex-wrap items-center gap-2">' . $actions . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('btn')) {
    /** Button/link style helper. */
    function btn(string $label, string $variant = 'primary', array $attrs = []): string
    {
        $variants = [
            'primary' => 'bg-brand-600 text-white hover:bg-brand-700',
            'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
            'ghost' => 'text-slate-600 hover:bg-slate-100',
            'danger' => 'bg-red-600 text-white hover:bg-red-700',
        ];
        $classes = 'inline-flex items-center justify-center gap-2 rounded-lg h-10 px-4 text-sm font-medium transition '
            . ($variants[$variant] ?? $variants['primary']);
        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= ' ' . $key . '="' . \App\Core\Helpers::e((string) $value) . '"';
        }
        return '<button type="button" class="' . $classes . '"' . $attrString . '>' . $label . '</button>';
    }
}

if (!function_exists('badge')) {
    function badge(string $label, string $tone = 'slate'): string
    {
        $tones = [
            'slate' => 'bg-slate-100 text-slate-700',
            'green' => 'bg-emerald-100 text-emerald-700',
            'blue' => 'bg-brand-100 text-brand-700',
            'red' => 'bg-red-100 text-red-700',
            'amber' => 'bg-amber-100 text-amber-700',
        ];
        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '
            . ($tones[$tone] ?? $tones['slate']) . '">' . \App\Core\Helpers::e($label) . '</span>';
    }
}

if (!function_exists('pagination')) {
    /**
     * Render a pager for paginated listings.
     *
     * @param array $pag ['page'=>int,'perPage'=>int,'total'=>int,'path'=>string]
     * @param array $query Current query-string filters to preserve across pages.
     */
    function pagination(array $pag, array $query = []): string
    {
        $total = (int) ($pag['total'] ?? 0);
        $perPage = max(1, (int) ($pag['perPage'] ?? 25));
        $page = max(1, (int) ($pag['page'] ?? 1));
        $pages = (int) ceil($total / $perPage);
        if ($pages <= 1) {
            return '';
        }

        $unset = ['generator_id', 'fuel_type', 'tank', 'from', 'to', 'page'];
        foreach ($unset as $k) {
            $query[$k] = '';
        }
        $query = array_filter($query, static fn ($v) => $v !== '' && $v !== null);

        $link = static function (int $n) use ($pag, $query): string {
            $query['page'] = $n;
            $qs = http_build_query($query);
            return htmlspecialchars(($pag['path'] ?? '') . ($qs !== '' ? '?' . $qs : ''), ENT_QUOTES);
        };

        $html = '<nav class="mt-4 flex flex-wrap items-center justify-between gap-3 px-1 text-sm no-print" aria-label="Pagination">';
        $from = ($page - 1) * $perPage + 1;
        $to = min($total, $page * $perPage);
        $html .= '<p class="text-slate-500">Showing <span class="font-medium text-slate-700">' . $from . '–' . $to . '</span> of <span class="font-medium text-slate-700">' . $total . '</span></p>';
        $html .= '<div class="flex items-center gap-1">';

        $btn = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 transition';
        $idle = 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50';
        $active = 'border-brand-600 bg-brand-600 text-white';
        $disabled = 'border-slate-200 bg-slate-50 text-slate-300 pointer-events-none';

        // Windowed page numbers with first/last + ellipsis.
        $nums = [];
        for ($n = 1; $n <= $pages; $n++) {
            if ($n === 1 || $n === $pages || abs($n - $page) <= 2) {
                $nums[] = $n;
            }
        }
        $prev = 0;
        foreach ($nums as $n) {
            if ($prev && $n - $prev > 1) {
                $html .= '<span class="px-1 text-slate-400">…</span>';
            }
            $cls = $n === $page ? $btn . ' ' . $active : $btn . ' ' . $idle;
            $html .= '<a href="' . $link($n) . '" class="' . $cls . '">' . $n . '</a>';
            $prev = $n;
        }

        $html .= '</div></nav>';
        return $html;
    }
}
