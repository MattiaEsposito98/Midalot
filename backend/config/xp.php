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

    // Monete del negozio avatar: arrivano insieme agli XP (stessa fonte, una
    // volta sola) piu' un bonus a ogni livello raggiunto.
    'coins_per_xp' => 0.2,
    'level_up_coins' => 20,
    // Monete per chi entra: ogni giorno, piu' un bonus a chi entra almeno
    // `weekly_login_days` giorni nella stessa settimana (lunedi'-domenica).
    'login_coins' => 3,
    'weekly_login_days' => 3,
    'weekly_login_coins' => 50,
    'level_up_coins_step' => 5, // bonus livello = level_up_coins + step * livello
];
