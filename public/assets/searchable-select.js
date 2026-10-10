/**
 * Searchable Select — dependency-free enhancement for native <select> elements.
 *
 * Any <select data-searchable> is progressively enhanced into a dropdown with
 * a built-in search box, while the original select stays in the DOM (hidden)
 * so form submission and existing JS (e.g. modal pre-fill) keep working.
 *
 * Works on desktop (click to open) and mobile/touch (tap to open).
 */
(function () {
    'use strict';

    var SEARCH_ICON = '<svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>';
    var CARET_ICON = '<svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>';

    function norm(s) {
        return String(s || '').toLowerCase().trim();
    }

    function enhance(sel) {
        if (sel.dataset.searchReady === '1') return;
        sel.dataset.searchReady = '1';

        // Wrapper
        var wrap = document.createElement('div');
        wrap.className = 'relative ss-wrap';
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);
        sel.classList.add('hidden');

        // Trigger button (mimics the native select styling)
        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ss-trigger flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 text-left text-sm text-slate-700 transition hover:bg-slate-50 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        var label = document.createElement('span');
        label.className = 'truncate';
        trigger.appendChild(label);
        trigger.insertAdjacentHTML('beforeend', CARET_ICON);
        wrap.appendChild(trigger);

        // Panel
        var panel = document.createElement('div');
        panel.className = 'ss-panel searchable-panel absolute left-0 right-0 z-30 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-lg';
        panel.innerHTML =
            '<div class="mb-2 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5">' +
                SEARCH_ICON +
                '<input type="text" class="ss-search w-full text-sm text-slate-700 placeholder\\:text-slate-400 focus:outline-none" placeholder="Search…" autocomplete="off">' +
            '</div>' +
            '<ul class="ss-list searchable-list overflow-y-auto text-sm" role="listbox"></ul>' +
            '<p class="ss-empty hidden px-2 py-3 text-center text-sm text-slate-500">No matches found.</p>';
        wrap.appendChild(panel);

        var searchInput = panel.querySelector('.ss-search');
        var listEl = panel.querySelector('.ss-list');
        var emptyEl = panel.querySelector('.ss-empty');

        function syncLabel() {
            var opt = sel.options[sel.selectedIndex];
            label.textContent = opt ? opt.textContent : '';
        }

        function buildList(filter) {
            listEl.innerHTML = '';
            var shown = 0;
            Array.prototype.forEach.call(sel.options, function (opt, idx) {
                if (filter && norm(opt.textContent).indexOf(filter) === -1) return;
                shown++;
                var li = document.createElement('li');
                var selected = sel.value === opt.value;
                li.className = 'cursor-pointer select-none rounded-lg px-2.5 py-1.5 ' +
                    (selected ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-700 hover:bg-slate-100');
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', selected ? 'true' : 'false');
                li.textContent = opt.textContent;
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    sel.value = opt.value;
                    sel.selectedIndex = idx;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    syncLabel();
                    close();
                });
                listEl.appendChild(li);
            });
            emptyEl.classList.toggle('hidden', shown > 0);
        }

        function open() {
            if (panel.classList.contains('open')) return;
            searchInput.value = '';
            buildList('');
            panel.classList.remove('hidden');
            panel.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            setTimeout(function () { searchInput.focus(); }, 0);
        }

        function close() {
            panel.classList.add('hidden');
            panel.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            if (panel.classList.contains('open')) { close(); } else { open(); }
        });

        searchInput.addEventListener('input', function () {
            buildList(norm(searchInput.value));
        });
        // Keep keystrokes inside the search field.
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { close(); trigger.focus(); }
            if (e.key === 'Enter') {
                e.preventDefault();
                var first = listEl.firstElementChild;
                if (first) first.dispatchEvent(new MouseEvent('mousedown', { bubbles: false }));
            }
        });
        searchInput.addEventListener('click', function (e) { e.stopPropagation(); });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });

        // Keep custom UI in sync when value is set programmatically (modals, filters).
        sel.addEventListener('change', syncLabel);
        new MutationObserver(syncLabel).observe(sel, { subtree: true, childList: true, characterData: true });
        ['value', 'selectedIndex'].forEach(function (prop) {
            var desc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, prop);
            if (desc && desc.set) {
                Object.defineProperty(sel, prop, {
                    configurable: true,
                    enumerable: desc.enumerable,
                    get: desc.get,
                    set: function (v) {
                        desc.set.call(this, v);
                        syncLabel();
                    }
                });
            }
        });

        syncLabel();
    }

    function init() {
        document.querySelectorAll('select[data-searchable]').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
