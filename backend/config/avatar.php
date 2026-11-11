<?php

/*
|--------------------------------------------------------------------------
| Avatar: opzioni di aspetto ammesse
|--------------------------------------------------------------------------
| Devono combaciare con frontend/src/components/Avatar3D/options.js e head.js.
| I colori sono liberi (esadecimali #rrggbb).
*/

return [
    'appearance' => [
        'gender' => ['m', 'f'],
        'faceShape' => ['ovale', 'squadrato', 'cuore', 'paffuto'],
        'brows' => ['morbide', 'folte', 'sottili', 'arcuate'],
        'mouth' => ['sorriso', 'sorrisone', 'furbetto', 'neutro'],
        'beard' => ['nessuna', 'baffi', 'pizzetto', 'corta', 'folta'],
        'hair' => [
            'corto', 'riga', 'spettinato', 'ricci', 'cresta', 'afro', 'caschetto',
            'lungo', 'coda', 'codini', 'chignon', 'treccia', 'rasato',
        ],
    ],

    'emblem_symbols' => ['star', 'cross', 'circle', 'book'],

    // Vestiti indossati da chi non ha ancora salvato l'avatar.
    'base_items' => [
        'torso' => 'maglietta_bianca',
        'legs' => 'jeans',
        'feet' => 'sneakers',
    ],

    /*
    | Vetrina settimanale del negozio.
    | Ogni lunedi' (00:00) cambiano gli oggetti in vetrina: `count` oggetti del negozio,
    | scelti a rotazione in modo automatico, costano `discount`% in meno fino a domenica.
    | Gli oggetti esclusivi non entrano mai nella rotazione. L'admin puo' scegliere
    | a mano gli oggetti di una settimana (Avatar e negozio -> Vetrina).
    */
    'showcase' => [
        'count' => 6,
        'discount' => 30,
    ],

    /*
    | Temi: oggetti che insieme fanno un personaggio completo. Gli oggetti hanno
    | la colonna `theme` (database/data/avatar_items.php, 8a colonna). Chi possiede
    | tutti gli oggetti attivi di un tema riceve in regalo l'oggetto `reward`
    | (un titolo che non si compra). Solo generi e archetipi con nomi e grafica
    | nostri: mai nomi, personaggi, loghi o design di marchi esistenti.
    */
    'themes' => [
        'supereroi' => ['name' => 'Supereroi', 'icon' => 'bi-lightning-charge-fill', 'reward' => 'titolo_set_eroe'],
        'magia' => ['name' => 'Scuola di magia', 'icon' => 'bi-magic', 'reward' => 'titolo_set_magia'],
        'cavalieri' => ['name' => 'Cavalieri e draghi', 'icon' => 'bi-shield-fill', 'reward' => 'titolo_set_cavaliere'],
        'pirati' => ['name' => 'Pirati', 'icon' => 'bi-compass-fill', 'reward' => 'titolo_set_pirata'],
        'spazio' => ['name' => 'Spazio', 'icon' => 'bi-rocket-takeoff-fill', 'reward' => 'titolo_set_spazio'],
        'ninja' => ['name' => 'Ninja', 'icon' => 'bi-wind', 'reward' => 'titolo_set_ninja'],
        // Stagionali: oggetti esclusivi, nascosti finche' l'admin non li apre (con scadenza).
        'natale' => ['name' => 'Natale', 'icon' => 'bi-snow', 'reward' => 'titolo_set_natale'],
        'halloween' => ['name' => 'Halloween', 'icon' => 'bi-moon-stars-fill', 'reward' => 'titolo_set_halloween'],
        'carnevale' => ['name' => 'Carnevale', 'icon' => 'bi-balloon-fill', 'reward' => 'titolo_set_carnevale'],
    ],

    // Prezzo proposto per rarita' quando un oggetto non ne ha uno (es. un esclusivo appena aperto).
    'default_prices' => [
        'base' => 100,
        'comune' => 150,
        'raro' => 400,
        'epico' => 900,
        'leggendario' => 2000,
    ],

    /*
    | Colori degli oggetti con tavolozza: [colore, livello, vecchio oggetto].
    | - livello: dal quale il colore e' disponibile (1 = subito, se si possiede l'oggetto).
    | - vecchio oggetto: key dell'oggetto separato che esisteva prima (es. maglietta
    |   rossa comprata nel negozio). Chi lo possedeva ha quel colore sbloccato per
    |   sempre e i vecchi avatar salvati vengono convertiti in automatico.
    | Il primo colore e' quello di partenza. Gli esadecimali sono nel frontend
    | (src/components/Avatar3D/palettes.js), con le stesse chiavi.
    */
    'palettes' => [
        'maglietta_bianca' => [
            ['bianco', 1, null], ['rosso', 1, 'maglietta_rossa'], ['blu', 1, 'maglietta_blu'],
            ['verde', 2, 'maglietta_verde'], ['giallo', 3, 'maglietta_gialla'], ['rosa', 4, 'maglietta_rosa'],
            ['nero', 5, 'maglietta_nera'], ['viola', 7, null], ['arancione', 9, null],
            ['azzurro', 12, null], ['oro', 20, null],
        ],
        'jeans' => [
            ['blu', 1, null], ['nero', 2, 'jeans_neri'], ['grigio', 4, null],
            ['chiaro', 6, null], ['bianco', 9, null], ['bordeaux', 15, null],
        ],
        'sneakers' => [
            ['classiche', 1, null], ['nere', 2, 'sneakers_nere'], ['blu', 3, null],
            ['verdi', 5, null], ['rosa', 7, null], ['gialle', 10, null],
        ],
        'cappellino' => [
            ['rosso', 1, null], ['blu', 1, 'cappellino_blu'], ['verde', 1, 'cappellino_verde'],
            ['nero', 5, null], ['rosa', 7, null], ['giallo', 10, null], ['viola', 15, null],
        ],
        'gonna_rossa' => [
            ['rosso', 1, null], ['blu', 1, 'gonna_blu'], ['nero', 3, null], ['rosa', 4, null],
            ['verde', 6, null], ['viola', 9, null], ['giallo', 12, null],
        ],
    ],

    'default' => [
        'gender' => 'm',
        'skin' => '#f6c9a4',
        'faceShape' => 'ovale',
        'brows' => 'morbide',
        'mouth' => 'sorriso',
        'beard' => 'nessuna',
        'freckles' => false,
        'hair' => 'corto',
        'hairColor' => '#5a3825',
        'eyes' => '#2b1d14',
        'emblem' => ['bg' => '#2f5fd0', 'fg' => '#ffd54a', 'symbol' => 'star'],
    ],
];
