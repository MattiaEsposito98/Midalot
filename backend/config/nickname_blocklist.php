<?php

/*
 * Parole vietate nei nickname (e nei nomi degli amici invitati a una sfida).
 * Usata da App\Support\NicknameFilter. Le parole si scrivono in minuscolo, senza accenti:
 * il filtro le confronta dopo aver tolto separatori (. _ - spazi), trasformato i numeri
 * "leet" in lettere (0=o 1=i 3=e 4=a 5=s 7=t) e ridotto le lettere ripetute (zz -> z).
 * Quindi "st_oca_zzo", "st0c4zz0" e "stocazzzo" vengono tutti riconosciuti.
 *
 * Non e' un filtro perfetto (chi vuole aggirarlo ci riesce), e' una protezione minima.
 */
return [

    // Bloccate ovunque compaiano nel nickname. Solo radici lunghe e inequivocabili,
    // per non bloccare nomi innocenti che le contengono per caso.
    'contains' => [
        // parolacce
        'cazzo', 'cazzi', 'minchia', 'minchie', 'stronz', 'vaffanc', 'fanculo', 'puttan',
        'troia', 'troie', 'mignott', 'zoccol', 'bocchin', 'pompin', 'coglion', 'merda', 'merde',
        'bastard', 'mortacci', 'sucami', 'sborra', 'inculat', 'porno', 'ricchion', 'froci',
        // inglese
        'fuck', 'shit', 'bitch', 'faggot', 'asshole', 'motherf',
        // nomi che non si possono usare
        'hitler', 'mussolini', 'midalot',
    ],

    // Bloccate solo come parola intera (da sole o separate da . _ -): sono corte e
    // compaiono dentro nomi normali ("penelope", "figaro", "nazionale", "badminton").
    'words' => [
        'culo', 'culi', 'figa', 'fica', 'pene', 'tette', 'tetta', 'sega', 'seghe', 'suca',
        'sesso', 'sex', 'dick', 'pussy', 'whore', 'slut', 'troione', 'frocio', 'negro', 'negri',
        'nazi', 'duce', 'isis', 'cunt', 'nigger', 'nigga',
        // ruoli riservati: nessuno deve potersi spacciare per lo staff
        'admin', 'administrator', 'amministratore', 'moderatore', 'moderator', 'staff', 'supporto', 'support',
    ],

    // Bestemmie: un nome sacro attaccato a un insulto, in qualsiasi ordine
    // ("porcodio", "diocane", "porcamadonna", "madonnaputtana"...).
    'sacred' => ['dio', 'madonna', 'cristo', 'gesu', 'gesucristo', 'signore'],
    'insults' => [
        'cane', 'porco', 'porca', 'bestia', 'maiale', 'ladro', 'boia', 'merda', 'stronzo',
        'serpente', 'infame', 'bastardo', 'schifoso', 'troia', 'puttana', 'impestato', 'lurido',
    ],
];
