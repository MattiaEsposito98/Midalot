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
