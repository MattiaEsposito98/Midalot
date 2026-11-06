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
