<?php

/*
|--------------------------------------------------------------------------
| Invita un amico
|--------------------------------------------------------------------------
| Ogni utente ha un link personale (/register?ref=CODICE). I premi arrivano
| quando l'amico gioca davvero, cosi' gli account finti non rendono nulla.
*/

return [
    // Monete di benvenuto all'amico invitato (al primo XP, cioe' al primo accesso dopo la verifica email).
    'welcome_coins' => 50,

    // Monete a chi ha invitato, quando l'amico raggiunge questi livelli.
    'level_rewards' => [
        3 => 100,
        10 => 300,
    ],

    // Massimo di amici premiati al mese (per ogni soglia di livello).
    'monthly_cap' => 10,

    // Titolo regalato a chi porta questo numero di amici al livello 3.
    'ambassador_friends' => 3,
    'ambassador_item' => 'titolo_ambasciatore',

    // Inviti via email: massimo al giorno per utente e giorni prima di poter
    // reinvitare lo stesso indirizzo.
    'emails_per_day' => 5,
    'same_email_days' => 7,
];
