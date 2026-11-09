<?php

/*
|--------------------------------------------------------------------------
| Obiettivi (premi per traguardi)
|--------------------------------------------------------------------------
| Ogni obiettivo si raggiunge quando la metrica arriva alla soglia e regala
| monete e/o un oggetto esclusivo dell'avatar (key in avatar_items, senza
| livello ne' prezzo: si ottiene solo cosi').
|
| Metriche disponibili (vedi App\Services\AchievementService::metric):
|   challenges_played  sfide giocate fino in fondo
|   challenges_won     sfide vinte
|   login_streak       giorni di accesso consecutivi (record personale)
|   quiz | minigioco | training | midalario   partite completate per tipo
|                      (training: Training diversi completati)
|   games              partite totali (le quattro sopra + sfide giocate)
|   shop_purchases     oggetti comprati nel negozio
|
| Per aggiungere un obiettivo basta una riga qui (ed eventualmente l'oggetto
| nel catalogo): chi ha gia' raggiunto la soglia lo riceve al prossimo accesso.
*/

return [
    'prima_sfida' => ['name' => 'Prima sfida', 'description' => 'Gioca la tua prima sfida', 'icon' => 'bi-lightning-charge', 'metric' => 'challenges_played', 'target' => 1, 'coins' => 50, 'item' => null],
    'sfidante' => ['name' => 'Sfidante', 'description' => 'Gioca 10 sfide', 'icon' => 'bi-lightning-charge-fill', 'metric' => 'challenges_played', 'target' => 10, 'coins' => 150, 'item' => null],
    'duellante' => ['name' => 'Duellante', 'description' => 'Vinci 10 sfide', 'icon' => 'bi-trophy', 'metric' => 'challenges_won', 'target' => 10, 'coins' => 200, 'item' => 'titolo_duellante'],
    'pugno_di_ferro' => ['name' => 'Pugno di ferro', 'description' => 'Vinci 25 sfide', 'icon' => 'bi-hand-index-thumb', 'metric' => 'challenges_won', 'target' => 25, 'coins' => 300, 'item' => 'guanti_duellante'],
    'campione_sfide' => ['name' => 'Campione delle Sfide', 'description' => 'Vinci 50 sfide', 'icon' => 'bi-trophy-fill', 'metric' => 'challenges_won', 'target' => 50, 'coins' => 500, 'item' => 'titolo_campione_sfide'],

    'costanza' => ['name' => 'Costanza', 'description' => 'Accedi 7 giorni di fila', 'icon' => 'bi-calendar-check', 'metric' => 'login_streak', 'target' => 7, 'coins' => 100, 'item' => 'cornice_costanza'],
    'instancabile' => ['name' => 'Instancabile', 'description' => 'Accedi 30 giorni di fila', 'icon' => 'bi-calendar2-heart', 'metric' => 'login_streak', 'target' => 30, 'coins' => 500, 'item' => 'titolo_instancabile'],

    'quizzarolo' => ['name' => 'Quizzarolo', 'description' => 'Completa 10 Quiz One Shot', 'icon' => 'bi-ui-checks-grid', 'metric' => 'quiz', 'target' => 10, 'coins' => 100, 'item' => null],
    'quiz_master' => ['name' => 'Quiz Master', 'description' => 'Completa 50 Quiz One Shot', 'icon' => 'bi-mortarboard', 'metric' => 'quiz', 'target' => 50, 'coins' => 300, 'item' => null],
    'giocherellone' => ['name' => 'Giocherellone', 'description' => 'Completa 25 minigiochi', 'icon' => 'bi-joystick', 'metric' => 'minigioco', 'target' => 25, 'coins' => 200, 'item' => null],
    'secchione' => ['name' => 'Secchione', 'description' => 'Completa 20 Training diversi', 'icon' => 'bi-book', 'metric' => 'training', 'target' => 20, 'coins' => 150, 'item' => 'titolo_secchione'],
    'veterano_midalario' => ['name' => 'Veterano del Midalario', 'description' => 'Partecipa a 5 Midalario', 'icon' => 'bi-broadcast', 'metric' => 'midalario', 'target' => 5, 'coins' => 250, 'item' => 'titolo_veterano_midalario'],
    'maratoneta' => ['name' => 'Maratoneta', 'description' => 'Gioca 100 partite in tutto', 'icon' => 'bi-person-walking', 'metric' => 'games', 'target' => 100, 'coins' => 300, 'item' => 'mantello_maratoneta'],

    'collezionista' => ['name' => 'Collezionista', 'description' => 'Compra 5 oggetti nel negozio', 'icon' => 'bi-bag-heart', 'metric' => 'shop_purchases', 'target' => 5, 'coins' => 100, 'item' => null],
];
