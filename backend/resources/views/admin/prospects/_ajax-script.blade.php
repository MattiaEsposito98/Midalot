{{-- Elenco clienti: filtri, ordinamento, pagine, stato, promemoria ed eliminazione senza ricaricare la pagina. --}}
{{-- Senza JavaScript tutto continua a funzionare con i normali invii di modulo. --}}
<div id="ajax-toast" class="position-fixed bottom-0 start-0 m-3" style="z-index: 2000; max-width: 360px; display: none;" role="status" aria-live="polite"></div>
<script>
    (function () {
        var form = document.getElementById('prospects-filters');
        var tabsBox = document.getElementById('prospects-tabs');
        var tableBox = document.getElementById('prospects-table');
        var exportLink = document.getElementById('prospects-export');
        var resetLink = document.getElementById('prospects-reset');
        if (!form || !tabsBox || !tableBox) { return; }

        var DEFAULTS = { sort: 'created_at', direction: 'desc', per_page: '50' };
        var IGNORED_FOR_RESET = ['sort', 'direction', 'per_page'];
        var baseUrl = form.getAttribute('action');
        var controller = null;
        var debounceTimer = null;
        var toastTimer = null;
        var csrf = document.querySelector('meta[name="csrf-token"]');
        csrf = csrf ? csrf.content : (form.querySelector('input[name="_token"]') || {}).value;

        // Il token CSRF viaggia comunque nel corpo del modulo (_token); l'intestazione e' solo un di piu' se la pagina lo espone.
        function ajaxHeaders(accept) {
            var h = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': accept };
            if (csrf) { h['X-CSRF-TOKEN'] = csrf; }
            return h;
        }

        function toast(text, kind) {
            var el = document.getElementById('ajax-toast');
            el.innerHTML = '<div class="alert alert-' + kind + ' shadow mb-0 py-2"></div>';
            el.firstChild.textContent = text;
            el.style.display = 'block';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { el.style.display = 'none'; }, kind === 'success' ? 2500 : 8000);
        }

        // Indirizzo con i valori attuali del modulo (i campi vuoti e i valori di default non compaiono).
        function currentUrl(extra) {
            var params = new URLSearchParams();
            new FormData(form).forEach(function (value, key) {
                if (value === '' || key === '_token') { return; }
                if (DEFAULTS[key] !== undefined && DEFAULTS[key] === value) { return; }
                params.set(key, value);
            });
            Object.keys(extra || {}).forEach(function (k) { params.set(k, extra[k]); });
            var qs = params.toString();
            return baseUrl + (qs ? '?' + qs : '');
        }

        // Porta i campi del modulo allo stato dell'indirizzo (serve quando si clicca su tab, ordinamento, pagine).
        function syncFormFromUrl(url) {
            var params = new URL(url, window.location.origin).searchParams;
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!el.name || el.name === '_token') { return; }
                var value = params.has(el.name) ? params.get(el.name) : (DEFAULTS[el.name] !== undefined ? DEFAULTS[el.name] : '');
                el.value = value;
            });
        }

        function hasActiveFilters() {
            var active = false;
            new FormData(form).forEach(function (value, key) {
                if (key !== '_token' && IGNORED_FOR_RESET.indexOf(key) === -1 && value !== '') { active = true; }
            });
            return active;
        }

        function applyPayload(html) {
            var tpl = document.createElement('template');
            tpl.innerHTML = html.trim();
            var payload = tpl.content.querySelector('#ajax-payload');
            if (!payload) { throw new Error('risposta non valida'); }
            tabsBox.innerHTML = payload.querySelector('[data-part="tabs"]').innerHTML;
            tableBox.innerHTML = payload.querySelector('[data-part="table"]').innerHTML;

            if (exportLink) {
                exportLink.href = payload.dataset.exportUrl;
                exportLink.querySelector('span').textContent = 'Esporta Excel' +
                    (payload.dataset.hasFilters === '1' ? ' (' + payload.dataset.total + ' filtrati)' : '');
            }
            if (resetLink) { resetLink.hidden = payload.dataset.hasFilters !== '1'; }
            var totalAll = document.getElementById('prospects-total-all');
            if (totalAll) { totalAll.textContent = payload.dataset.totalAll; }
            updateNavCounter(parseInt(payload.dataset.due, 10) || 0);
        }

        // Contatore dei promemoria scaduti nel menu in alto (voce "Clienti da contattare" e gruppo che la contiene).
        function updateNavCounter(due) {
            var link = document.querySelector('.admin-nav-menu a[href$="/admin/clienti"]');
            if (!link) { return; }
            var badge = link.querySelector('.admin-nav-count');
            var old = badge ? (parseInt(badge.textContent, 10) || 0) : 0;
            if (old === due) { return; }
            var delta = due - old;

            if (due > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'admin-nav-count ms-auto';
                    link.appendChild(badge);
                }
                badge.textContent = due;
            } else if (badge) {
                badge.remove();
            }

            var button = link.closest('.dropdown') && link.closest('.dropdown').querySelector('button.admin-nav-button');
            if (!button) { return; }
            var groupBadge = button.querySelector('.admin-nav-count');
            var total = (groupBadge ? (parseInt(groupBadge.textContent, 10) || 0) : 0) + delta;
            if (total > 0) {
                if (!groupBadge) {
                    groupBadge = document.createElement('span');
                    groupBadge.className = 'admin-nav-count';
                    button.appendChild(groupBadge);
                }
                groupBadge.textContent = total;
            } else if (groupBadge) {
                groupBadge.remove();
            }
        }

        function setBusy(on) { tableBox.style.opacity = on ? '0.55' : ''; tableBox.style.transition = 'opacity .15s'; }

        // Carica l'elenco per un indirizzo e sostituisce solo tab e tabella.
        function load(url, opts) {
            opts = opts || {};
            if (controller) { controller.abort(); }
            controller = new AbortController();
            var mine = controller;
            setBusy(true);

            return fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal: mine.signal,
                credentials: 'same-origin'
            }).then(function (res) {
                if (!res.ok) { throw new Error('HTTP ' + res.status); }
                return res.text();
            }).then(function (html) {
                if (mine !== controller) { return; }
                applyPayload(html);
                if (opts.fromLink) { syncFormFromUrl(url); }
                history.replaceState(null, '', url);
                setBusy(false);
            }).catch(function (err) {
                if (err && err.name === 'AbortError') { return; }
                setBusy(false);
                window.location.href = url; // in caso di problemi ricade sul caricamento normale
            });
        }

        function reloadCurrent() {
            // Mantiene la pagina in cui ci si trova (se esiste ancora) e i filtri attuali.
            var page = new URLSearchParams(window.location.search).get('page');
            return load(currentUrl(page ? { page: page } : {}));
        }

        // --- filtri: aggiornano mentre si scrive o si sceglie ---
        form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(debounceTimer); load(currentUrl()); });
        form.addEventListener('change', function (e) {
            if (e.target.matches('select, input[type="number"]')) { clearTimeout(debounceTimer); load(currentUrl()); }
        });
        form.addEventListener('input', function (e) {
            if (e.target.matches('input[type="search"], input[type="number"]')) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () { load(currentUrl()); }, 350);
            }
        });

        if (resetLink) {
            resetLink.addEventListener('click', function (e) {
                e.preventDefault();
                syncFormFromUrl(baseUrl);
                load(baseUrl);
            });
        }

        // --- tab di stato, ordinamento colonne e pagine: link che non ricaricano la pagina ---
        function interceptLinks(e) {
            var a = e.target.closest('a[href]');
            if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) { return; }
            var url = new URL(a.href, window.location.origin);
            if (url.origin !== window.location.origin || url.pathname !== new URL(baseUrl, window.location.origin).pathname) { return; }
            if (a.target === '_blank' || a.hasAttribute('download')) { return; }
            e.preventDefault();
            load(url.pathname + url.search, { fromLink: true });
        }
        tabsBox.addEventListener('click', interceptLinks);
        tableBox.addEventListener('click', interceptLinks);

        // --- stato e promemoria: salvati in background ---
        tableBox.addEventListener('change', function (e) {
            var f = e.target.closest('form.js-status-form');
            if (!f) { return; }
            var fields = f.querySelectorAll('select, input');
            var body = new FormData(f);              // letto prima di bloccare i campi (quelli bloccati non vengono inviati)
            fields.forEach(function (x) { x.disabled = true; });

            fetch(f.action, {
                method: 'POST',
                body: body,
                headers: ajaxHeaders('application/json'),
                credentials: 'same-origin'
            }).then(function (res) {
                if (!res.ok) { throw new Error('HTTP ' + res.status); }
                return res.json();
            }).then(function (data) {
                toast(data.message || 'Salvato.', 'success');
                return reloadCurrent();
            }).catch(function () {
                fields.forEach(function (x) { x.disabled = false; });
                toast('Salvataggio non riuscito. Riprova.', 'danger');
            });
        });

        // --- eliminazione in background (la conferma resta quella del pulsante) ---
        tableBox.addEventListener('submit', function (e) {
            var f = e.target.closest('form.js-delete-form');
            if (!f) { return; }
            e.preventDefault();
            fetch(f.action, {
                method: 'POST',
                body: new FormData(f),
                headers: ajaxHeaders('application/json'),
                credentials: 'same-origin'
            }).then(function (res) {
                if (!res.ok) { throw new Error('HTTP ' + res.status); }
                return res.json();
            }).then(function (data) {
                toast(data.message || 'Eliminato.', 'success');
                return reloadCurrent();
            }).catch(function () { toast('Eliminazione non riuscita. Riprova.', 'danger'); });
        });
    })();
</script>
