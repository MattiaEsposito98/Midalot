<?php

/*
|--------------------------------------------------------------------------
| Catalogo oggetti dell'avatar
|--------------------------------------------------------------------------
| [key, nome, slot, rarita', livello di sblocco|null, prezzo|null, livello minimo negozio]
|
| - livello di sblocco: l'oggetto si riceve in regalo a quel livello.
| - prezzo: l'oggetto si puo' comprare nel negozio con le monete.
|
| La key deve esistere anche nel frontend (src/components/Avatar3D/items.jsx),
| dove c'e' la grafica. Per aggiungere oggetti: si aggiunge la riga qui e il
| modello nel frontend, poi `php artisan db:seed --class=AvatarItemSeeder`.
| Colori: gli oggetti con tavolozza sono in config/avatar.php (palettes).
| Il seeder inserisce solo le key nuove: prezzi e livelli cambiati dall'admin
| non vengono sovrascritti.
*/

return [
    // ---------------------------------------------------- Set Apprendista (1-10)
    ['maglietta_bianca', 'Maglietta', 'torso', 'base', 1, null, 1],
    ['jeans', 'Jeans', 'legs', 'base', 1, null, 1],
    ['sneakers', 'Scarpe da ginnastica', 'feet', 'base', 1, null, 1],
    ['occhiali_tondi', 'Occhiali tondi', 'face', 'comune', 2, null, 1],
    ['matita', 'Matita gigante', 'handR', 'comune', 3, null, 1],
    ['cappellino', 'Cappellino', 'head', 'comune', 4, null, 1],
    ['libro_appunti', 'Libro degli Appunti', 'handL', 'raro', 5, null, 1],
    ['maglietta_midalot', 'Maglietta Midalot', 'torso', 'comune', 6, null, 1],
    ['zainetto', 'Zainetto', 'back', 'comune', 7, null, 1],
    ['scarpe_colorate', 'Scarpe colorate', 'feet', 'comune', 8, null, 1],
    ['sciarpa', 'Sciarpa', 'neck', 'comune', 9, null, 1],
    ['tocco_laureato', 'Tocco da laureato', 'head', 'epico', 10, null, 1],
    ['mantellina', "Mantellina dell'Apprendista", 'back', 'epico', 10, null, 1],

    // ---------------------------------------------------- Set Esploratore (11-20)
    ['pantaloni_cargo', 'Pantaloni cargo', 'legs', 'comune', 11, null, 1],
    ['torcia', 'Torcia', 'handR', 'comune', 12, null, 1],
    ['cappello_esploratore', 'Cappello da esploratore', 'head', 'comune', 13, null, 1],
    ['bussola', 'Bussola', 'handL', 'comune', 14, null, 1],
    ['lanterna', 'Lanterna magica', 'handL', 'raro', 15, null, 1],
    ['stivali_trekking', 'Stivali da trekking', 'feet', 'comune', 16, null, 1],
    ['gilet_esploratore', 'Gilet da esploratore', 'torso', 'comune', 17, null, 1],
    ['occhiali_aviatore', 'Occhiali da aviatore', 'face', 'comune', 18, null, 1],
    ['mantello_viaggio', 'Mantello da viaggio', 'back', 'comune', 19, null, 1],
    ['sfondo_foresta', 'Foresta', 'background', 'epico', 20, null, 1],

    // ---------------------------------------------------- Set Studioso (21-30)
    ['camicia_studioso', 'Camicia da studioso', 'torso', 'comune', 21, null, 1],
    ['pergamena', 'Pergamena', 'handL', 'comune', 22, null, 1],
    ['occhiali_mezzaluna', 'Occhiali a mezzaluna', 'face', 'comune', 23, null, 1],
    ['penna_oca', "Penna d'oca", 'handR', 'comune', 24, null, 1],
    ['gufo', 'Gufo compagno', 'pet', 'epico', 25, null, 1],
    ['pantaloni_eleganti', 'Pantaloni eleganti', 'legs', 'comune', 26, null, 1],
    ['scarpe_cuoio', 'Scarpe di cuoio', 'feet', 'comune', 27, null, 1],
    ['cappello_sapiente', 'Cappello del sapiente', 'head', 'raro', 28, null, 1],
    ['tomo_antico', 'Tomo antico', 'handL', 'raro', 29, null, 1],
    ['sfondo_biblioteca', 'Biblioteca', 'background', 'epico', 30, null, 1],

    // ---------------------------------------------------- Set Cavaliere (31-40)
    ['spada_legno', 'Spada di legno', 'handR', 'comune', 31, null, 1],
    ['elmo_leggero', 'Elmo leggero', 'head', 'comune', 32, null, 1],
    ['scudo', 'Scudo con stemma', 'handL', 'raro', 33, null, 1],
    ['armatura_cuoio', 'Armatura di cuoio', 'armor', 'comune', 34, null, 1],
    ['spada_acciaio', "Spada d'acciaio", 'handR', 'raro', 35, null, 1],
    ['gambali', 'Gambali', 'legs', 'comune', 36, null, 1],
    ['stivali_ferro', 'Stivali di ferro', 'feet', 'comune', 37, null, 1],
    ['mantello_rosso', 'Mantello rosso', 'back', 'raro', 38, null, 1],
    ['armatura_piastre', 'Armatura a piastre', 'armor', 'raro', 39, null, 1],
    ['sfondo_castello', 'Castello', 'background', 'epico', 40, null, 1],

    // ---------------------------------------------------- Set Leggenda (41-50)
    ['corona_alloro', "Corona d'alloro", 'head', 'raro', 41, null, 1],
    ['guanti_oro', "Guanti d'oro", 'hands', 'raro', 42, null, 1],
    ['bacchetta', 'Bacchetta magica', 'handR', 'raro', 43, null, 1],
    ['aura_scintille', 'Aura scintillante', 'aura', 'epico', 44, null, 1],
    ['spada_cristallo', 'Spada di cristallo', 'handR', 'epico', 45, null, 1],
    ['armatura_dorata', 'Armatura dorata', 'armor', 'epico', 46, null, 1],
    ['ali', 'Ali', 'back', 'epico', 47, null, 1],
    ['elmo_dorato', 'Elmo dorato', 'head', 'epico', 48, null, 1],
    ['aura_fiamme', 'Aura di fiamme', 'aura', 'epico', 49, null, 1],
    ['corona_leggenda', 'Corona della Leggenda', 'head', 'leggendario', 50, null, 1],
    ['sfondo_leggenda', 'Piedistallo della Leggenda', 'background', 'leggendario', 50, null, 1],

    // ---------------------------------------------------- Negozio
    ['maglia_righe', 'Maglia a righe', 'torso', 'raro', null, 250, 4],
    ['felpa_cappuccio', 'Felpa con cappuccio', 'torso', 'raro', null, 300, 5],

    ['pantaloni_tuta', 'Pantaloni della tuta', 'legs', 'comune', null, 150, 2],
    ['pantaloncini', 'Pantaloncini', 'legs', 'comune', null, 150, 2],
    ['gonna_rossa', 'Gonna', 'legs', 'comune', null, 150, 2],

    ['stivaletti_rosa', 'Stivaletti rosa', 'feet', 'comune', null, 200, 3],
    ['scarpe_oro', "Scarpe d'oro", 'feet', 'epico', null, 900, 15],

    ['fiocco', 'Fiocco', 'head', 'comune', null, 150, 2],
    ['berretto_lana', 'Berretto di lana', 'head', 'raro', null, 250, 6],
    ['cuffie', 'Cuffie', 'head', 'raro', null, 350, 8],
    ['cappello_pirata', 'Cappello da pirata', 'head', 'epico', null, 900, 15],

    ['occhiali_sole', 'Occhiali da sole', 'face', 'comune', null, 150, 3],
    ['occhiali_cuore', 'Occhiali a cuore', 'face', 'raro', null, 300, 6],

    ['gelato', 'Gelato', 'handL', 'comune', null, 200, 3],
    ['palloncino', 'Palloncino', 'handR', 'raro', null, 300, 5],
    ['microfono', 'Microfono', 'handR', 'raro', null, 350, 8],
    ['trofeo', 'Trofeo', 'handL', 'epico', null, 1000, 20],

    ['mantello_viola', 'Mantello viola', 'back', 'raro', null, 400, 12],

    ['pulcino', 'Pulcino', 'pet', 'raro', null, 500, 10],
    ['gatto', 'Gatto', 'pet', 'epico', null, 1200, 18],

    ['aura_cuori', 'Aura di cuori', 'aura', 'raro', null, 600, 12],

    ['sfondo_spiaggia', 'Spiaggia', 'background', 'raro', null, 500, 8],
    ['sfondo_spazio', 'Spazio', 'background', 'epico', null, 1200, 20],

    // ---------------------------------------------------- Titoli (scritta accanto al nickname)
    ['titolo_matricola', 'Matricola', 'title', 'comune', 3, null, 1],
    ['titolo_apprendista', 'Apprendista', 'title', 'epico', 10, null, 1],
    ['titolo_esploratore', 'Esploratore', 'title', 'epico', 20, null, 1],
    ['titolo_sapiente', 'Sapiente', 'title', 'epico', 30, null, 1],
    ['titolo_cavaliere', 'Cavaliere del Sapere', 'title', 'epico', 40, null, 1],
    ['titolo_leggenda', 'Leggenda di Midalot', 'title', 'leggendario', 50, null, 1],
    ['titolo_curioso', 'Curioso', 'title', 'comune', null, 150, 1],
    ['titolo_cervellone', 'Cervellone', 'title', 'comune', null, 300, 3],
    ['titolo_inarrestabile', 'Inarrestabile', 'title', 'raro', null, 400, 5],
    ['titolo_mago_trivia', 'Mago della Trivia', 'title', 'raro', null, 600, 8],
    ['titolo_asso_quiz', 'Asso dei Quiz', 'title', 'epico', null, 800, 10],
    ['titolo_enciclopedia', 'Enciclopedia vivente', 'title', 'epico', null, 1200, 15],

    // ---------------------------------------------------- Cornici (intorno al ritratto)
    ['cornice_bronzo', 'Cornice di bronzo', 'frame', 'raro', 5, null, 1],
    ['cornice_argento', "Cornice d'argento", 'frame', 'raro', 15, null, 1],
    ['cornice_oro', "Cornice d'oro", 'frame', 'epico', 25, null, 1],
    ['cornice_diamante', 'Cornice di diamante', 'frame', 'epico', 35, null, 1],
    ['cornice_arcobaleno', 'Cornice arcobaleno', 'frame', 'epico', 45, null, 1],
    ['cornice_leggenda', 'Cornice della Leggenda', 'frame', 'leggendario', 50, null, 1],
    ['cornice_cuori', 'Cornice di cuori', 'frame', 'comune', null, 300, 3],
    ['cornice_stelle', 'Cornice di stelle', 'frame', 'raro', null, 400, 5],
    ['cornice_neon', 'Cornice neon', 'frame', 'raro', null, 500, 8],
    ['cornice_fiamme', 'Cornice di fiamme', 'frame', 'epico', null, 900, 15],

    // ---------------------------------------------------- Esclusivi degli obiettivi (config/achievements.php)
    ['titolo_duellante', 'Duellante', 'title', 'raro', null, null, 1],
    ['titolo_campione_sfide', 'Campione delle Sfide', 'title', 'leggendario', null, null, 1],
    ['titolo_instancabile', 'Instancabile', 'title', 'epico', null, null, 1],
    ['titolo_secchione', 'Secchione', 'title', 'raro', null, null, 1],
    ['titolo_veterano_midalario', 'Veterano del Midalario', 'title', 'epico', null, null, 1],
    ['guanti_duellante', 'Guanti da duellante', 'hands', 'epico', null, null, 1],
    ['cornice_costanza', 'Cornice della costanza', 'frame', 'raro', null, null, 1],
    ['mantello_maratoneta', 'Mantello del maratoneta', 'back', 'epico', null, null, 1],
];
