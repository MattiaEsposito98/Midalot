{{-- Copia il messaggio negli appunti e apre la chat/profilo in una nuova scheda. Nessun invio automatico. --}}
<div id="copy-toast" class="position-fixed bottom-0 end-0 m-3" style="z-index: 2000; max-width: 360px; display: none;" role="status" aria-live="polite"></div>
<script>
    (function () {
        // Copia in modo sincrono (dentro il clic): non dipende da permessi, focus o attese.
        function copySync(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '0';
            ta.style.left = '0';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            ta.setSelectionRange(0, text.length);
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(ta);
            return ok;
        }

        var toastTimer = null;
        function toast(html, kind) {
            var el = document.getElementById('copy-toast');
            el.innerHTML = '<div class="alert alert-' + kind + ' shadow mb-0">' + html + '</div>';
            el.style.display = 'block';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { el.style.display = 'none'; }, kind === 'success' ? 5000 : 12000);
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-copy-message]');
            if (!btn) { return; }

            // Il messaggio sta nell'attributo oppure in una textarea (pagina di modifica: puo' essere ritoccato).
            var source = btn.dataset.copySource ? document.getElementById(btn.dataset.copySource) : null;
            var text = source ? source.value : btn.dataset.copyMessage;
            // Da telefono il link breve ig.me porta alla chat nell'app; da computer si usa instagram.com/m/.
            var isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');
            var url = (isMobile && btn.dataset.openUrlMobile) ? btn.dataset.openUrlMobile : btn.dataset.openUrl;

            // 1) copia subito; 2) se serve apre la scheda, sempre dentro il clic (cosi' il browser non la blocca).
            var copied = copySync(text);
            if (!copied && navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function () {
                    toast('<i class="bi bi-check2-circle"></i> Messaggio copiato. Incollalo con Ctrl+V.', 'success');
                }).catch(function () {
                    toast('<i class="bi bi-exclamation-triangle"></i> Copia non riuscita: seleziona il testo e copialo a mano.', 'danger');
                });
                copied = null; // esito mostrato dalla promessa
            }

            var win = url ? window.open(url, '_blank') : undefined;
            if (win) { try { win.opener = null; } catch (e) { /* ignora */ } }

            if (copied === null) { return; }
            if (!copied) {
                if (source) { source.focus(); source.select(); }
                toast('<i class="bi bi-exclamation-triangle"></i> Copia non riuscita: seleziona il testo e copialo a mano.', 'danger');
            } else if (url && !win) {
                toast('<i class="bi bi-exclamation-triangle"></i> Messaggio copiato, ma il browser ha bloccato la nuova scheda. '
                    + '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" class="alert-link">Apri la chat</a> '
                    + 'oppure consenti i popup per questo sito.', 'warning');
            } else {
                toast('<i class="bi bi-check2-circle"></i> Messaggio copiato. Incollalo nella chat con Ctrl+V.', 'success');
            }
        });
    })();
</script>
