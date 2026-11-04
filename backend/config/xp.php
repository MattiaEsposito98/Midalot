<?php

/*
|--------------------------------------------------------------------------
| Sistema XP e livelli
|--------------------------------------------------------------------------
| `games` e' il massimo di XP ottenibile in una partita. L'XP effettivo e'
| max * (floor + (1 - floor) * rapporto_punteggio): una quota fissa per aver
| giocato piu' una quota proporzionale alla prestazione.
|
| Per salire dal livello n al livello n+1 servono level_base + level_step * n
| XP, quindi ogni livello costa piu' del precedente.
*/

return [
    'games' => [
        'midalario' => 125,
        'quiz' => 80,
        'minigioco' => 60,
        'training' => 20,
        'login' => 10,
        'challenge' => 40,
    ],

    // Sfide: bonus extra e limiti anti-abuso (XP solo a sfida chiusa da entrambi).
    'challenge_win_bonus' => 25,
    'challenge_draw_bonus' => 10,
    'challenge_daily_cap' => 5,
    'challenge_pair_daily_cap' => 2,

    'floor' => 0.4,

    'level_base' => 100,
    'level_step' => 25,
];
