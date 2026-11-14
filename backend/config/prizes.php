<?php

/*
|--------------------------------------------------------------------------
| Premi in monete per posizione
|--------------------------------------------------------------------------
| Un elenco per ogni classifica: il primo valore e' per il 1° posto, il
| secondo per il 2° e cosi' via. Le posizioni oltre l'ultima non ricevono nulla.
|
| - monthly: classifica premi mensile (assegnata dal cron il 1° del mese,
|   App\Services\MonthlyBadgeAssigner).
| - midalario: ogni sessione del Midalario (assegnata alla chiusura della
|   partita, App\Services\MidalarioFinalizer).
|
| Pari merito: stessa posizione = stesso premio (art. 4.3 e 6.6 del
| Regolamento); chi viene dopo salta le posizioni occupate (due primi ->
| il successivo e' 3°). Chi ha 0 punti non riceve nulla.
| Le monete sono accreditate una sola volta (CoinEvent) e l'utente vede un
| avviso come per gli obiettivi (tabella prize_awards).
*/

return [
    'monthly' => [1000, 600, 400, 250, 150],
    'midalario' => [500, 300, 200, 120, 80],
];
