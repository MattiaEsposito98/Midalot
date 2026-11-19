{{-- Copia il messaggio negli appunti e poi apre la chat/profilo in una nuova scheda. Nessun invio automatico. --}}
<script>
    (function () {
        function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            return new Promise(function (resolve, reject) {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                document.body.removeChild(ta);
                ok ? resolve() : reject(new Error('copy failed'));
            });
        }

        function flash(btn, label) {
            var original = btn.dataset.label || btn.innerHTML;
            btn.dataset.label = original;
            btn.innerHTML = label;
            setTimeout(function () { btn.innerHTML = original; }, 2200);
        }

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-copy-message]');
            if (!btn) { return; }

            // Il messaggio sta nell'attributo oppure in una textarea (pagina di modifica: puo' essere ritoccato).
            var source = btn.dataset.copySource ? document.getElementById(btn.dataset.copySource) : null;
            var text = source ? source.value : btn.dataset.copyMessage;
            var url = btn.dataset.openUrl;

            copyText(text).then(function () {
                flash(btn, '<i class="bi bi-check2"></i> Copiato');
                if (url) { window.open(url, '_blank', 'noopener'); }
            }).catch(function () {
                flash(btn, 'Copia non riuscita');
                if (source) { source.focus(); source.select(); }
            });
        });
    })();
</script>
