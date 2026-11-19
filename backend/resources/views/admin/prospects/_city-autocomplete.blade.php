{{-- Suggerimenti città dopo la terza lettera (tabella cities, rotta /cities/search). Compila anche la regione. --}}
{{-- Variabili: $cityId (id dell'input città), $regionId (id del select regione) --}}
<datalist id="{{ $cityId }}-list"></datalist>
<script>
    (function () {
        const input = document.getElementById(@json($cityId));
        const region = document.getElementById(@json($regionId));
        const list = document.getElementById(@json($cityId) + '-list');
        if (!input || !list) return;

        input.setAttribute('list', list.id);
        input.setAttribute('autocomplete', 'off');

        let timer;
        let last = [];

        input.addEventListener('input', function () {
            // Se il testo coincide con un suggerimento, completa la regione.
            const hit = last.find(c => c.name.toLowerCase() === input.value.trim().toLowerCase());
            if (hit && region && hit.region) {
                const opt = Array.from(region.options).find(o => o.value.toLowerCase() === hit.region.toLowerCase());
                if (opt) region.value = opt.value;
            }

            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 3) { list.innerHTML = ''; last = []; return; }

            timer = setTimeout(async () => {
                try {
                    const res = await fetch('/cities/search?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    last = await res.json();
                    list.innerHTML = '';
                    last.forEach(c => {
                        const o = document.createElement('option');
                        o.value = c.name;
                        o.label = c.name + (c.province ? ' (' + c.province + ')' : '') + (c.region ? ' · ' + c.region : '');
                        list.appendChild(o);
                    });
                } catch (e) { /* senza suggerimenti il campo resta libero */ }
            }, 250);
        });
    })();
</script>
