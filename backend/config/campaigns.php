<?php

return [
    /*
    | Utenti del team (nickname, separati da virgola, in .env) le cui aperture dei link di campagna
    | NON vengono contate quando sono collegati al sito. Gli admin (is_admin) sono sempre esclusi.
    | I nickname stanno solo nel .env, non nel codice (la repo e' pubblica).
    */
    'excluded_nicknames' => array_values(array_filter(array_map(
        fn ($n) => mb_strtolower(trim($n)),
        explode(',', (string) env('CAMPAIGN_EXCLUDED_NICKNAMES', ''))
    ))),
];
