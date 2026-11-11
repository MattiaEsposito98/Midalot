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

    // ---------------------------------------------------- Temi (config/avatar.php -> themes), 8a colonna = tema
    // Supereroi
    ['eroe_maschera', "Maschera dell'eroe", 'face', 'comune', null, 200, 3, 'supereroi'],
    ['eroe_tuta', 'Tuta da eroe', 'torso', 'raro', null, 400, 5, 'supereroi'],
    ['eroe_pantaloni', 'Pantaloni da eroe', 'legs', 'comune', null, 200, 3, 'supereroi'],
    ['eroe_stivali', 'Stivali da eroe', 'feet', 'comune', null, 250, 4, 'supereroi'],
    ['eroe_guanti', 'Guanti da eroe', 'hands', 'comune', null, 200, 4, 'supereroi'],
    ['eroe_mantello', "Mantello dell'eroe", 'back', 'raro', null, 450, 6, 'supereroi'],
    ['eroe_scudo', "Scudo dell'eroe", 'handL', 'epico', null, 900, 10, 'supereroi'],
    ['eroe_martello', 'Martello del tuono', 'handR', 'epico', null, 900, 12, 'supereroi'],
    ['eroe_robot', 'Robottino aiutante', 'pet', 'raro', null, 600, 8, 'supereroi'],
    ['eroe_aura', 'Aura di energia', 'aura', 'raro', null, 600, 9, 'supereroi'],
    ['sfondo_citta', 'Città di notte', 'background', 'raro', null, 500, 6, 'supereroi'],
    ['cornice_fumetto', 'Cornice fumetto', 'frame', 'raro', null, 400, 5, 'supereroi'],
    ['titolo_set_eroe', 'Supereroe leggendario', 'title', 'leggendario', null, null, 1],

    // Scuola di magia
    ['magia_cappello', 'Cappello da mago', 'head', 'raro', null, 400, 4, 'magia'],
    ['magia_tunica', "Tunica dell'apprendista", 'torso', 'raro', null, 400, 5, 'magia'],
    ['magia_pantaloni', 'Pantaloni da mago', 'legs', 'comune', null, 200, 3, 'magia'],
    ['magia_scarpe', 'Scarpe a punta', 'feet', 'comune', null, 200, 3, 'magia'],
    ['magia_sciarpa', 'Sciarpa a righe', 'neck', 'comune', null, 200, 3, 'magia'],
    ['magia_bacchetta', 'Bacchetta magica', 'handR', 'epico', null, 900, 10, 'magia'],
    ['magia_libro', 'Libro degli incantesimi', 'handL', 'raro', null, 500, 7, 'magia'],
    ['magia_scopa', 'Scopa volante', 'back', 'epico', null, 800, 9, 'magia'],
    ['magia_civetta', 'Civetta delle nevi', 'pet', 'raro', null, 600, 8, 'magia'],
    ['magia_aura', 'Aura di rune', 'aura', 'raro', null, 600, 9, 'magia'],
    ['sfondo_torre', 'Torre dei maghi', 'background', 'raro', null, 500, 6, 'magia'],
    ['cornice_runica', 'Cornice runica', 'frame', 'raro', null, 400, 5, 'magia'],
    ['titolo_set_magia', 'Arcimago', 'title', 'leggendario', null, null, 1],

    // Cavalieri e draghi
    ['cav_elmo', 'Elmo col pennacchio', 'head', 'raro', null, 450, 5, 'cavalieri'],
    ['cav_armatura', 'Armatura del paladino', 'armor', 'epico', null, 900, 10, 'cavalieri'],
    ['cav_pantaloni', 'Gambali del paladino', 'legs', 'comune', null, 250, 4, 'cavalieri'],
    ['cav_stivali', 'Schinieri', 'feet', 'comune', null, 250, 4, 'cavalieri'],
    ['cav_guanti', "Guanti d'arme", 'hands', 'comune', null, 200, 4, 'cavalieri'],
    ['cav_mantello', 'Mantello del regno', 'back', 'raro', null, 450, 6, 'cavalieri'],
    ['cav_spada', 'Spada del paladino', 'handR', 'epico', null, 900, 11, 'cavalieri'],
    ['cav_scudo', 'Scudo con stemma', 'handL', 'raro', null, 500, 7, 'cavalieri'],
    ['cav_drago', 'Draghetto', 'pet', 'epico', null, 1200, 14, 'cavalieri'],
    ['cav_aura', 'Aura del valore', 'aura', 'raro', null, 600, 9, 'cavalieri'],
    ['sfondo_torneo', 'Campo del torneo', 'background', 'raro', null, 500, 6, 'cavalieri'],
    ['cornice_pietra', 'Cornice di pietra', 'frame', 'raro', null, 400, 5, 'cavalieri'],
    ['titolo_set_cavaliere', 'Paladino del regno', 'title', 'leggendario', null, null, 1],

    // Pirati
    ['pir_bandana', 'Bandana', 'head', 'comune', null, 200, 3, 'pirati'],
    ['pir_benda', "Benda sull'occhio", 'face', 'comune', null, 150, 3, 'pirati'],
    ['pir_giacca', 'Giacca da capitano', 'torso', 'raro', null, 450, 6, 'pirati'],
    ['pir_pantaloni', 'Pantaloni da marinaio', 'legs', 'comune', null, 200, 3, 'pirati'],
    ['pir_stivaloni', 'Stivaloni', 'feet', 'comune', null, 250, 4, 'pirati'],
    ['pir_mantello', 'Mantello del capitano', 'back', 'raro', null, 450, 6, 'pirati'],
    ['pir_sciabola', 'Sciabola', 'handR', 'raro', null, 500, 7, 'pirati'],
    ['pir_cannocchiale', 'Cannocchiale', 'handL', 'comune', null, 250, 4, 'pirati'],
    ['pir_pappagallo', 'Pappagallo', 'pet', 'epico', null, 1000, 12, 'pirati'],
    ['pir_aura', 'Aura di dobloni', 'aura', 'raro', null, 600, 9, 'pirati'],
    ['sfondo_nave', 'Ponte della nave', 'background', 'raro', null, 500, 6, 'pirati'],
    ['cornice_corda', 'Cornice di corda', 'frame', 'raro', null, 400, 5, 'pirati'],
    ['titolo_set_pirata', 'Capitano leggendario', 'title', 'leggendario', null, null, 1],

    // Spazio
    ['spa_casco', 'Casco da astronauta', 'head', 'raro', null, 500, 6, 'spazio'],
    ['spa_tuta', 'Tuta spaziale', 'torso', 'raro', null, 450, 5, 'spazio'],
    ['spa_pantaloni', 'Pantaloni spaziali', 'legs', 'comune', null, 200, 3, 'spazio'],
    ['spa_stivali', 'Stivali lunari', 'feet', 'comune', null, 250, 4, 'spazio'],
    ['spa_guanti', 'Guanti spaziali', 'hands', 'comune', null, 200, 4, 'spazio'],
    ['spa_jetpack', 'Zaino a reazione', 'back', 'epico', null, 1000, 12, 'spazio'],
    ['spa_raggi', 'Pistola a raggi', 'handR', 'raro', null, 500, 7, 'spazio'],
    ['spa_scanner', 'Scanner', 'handL', 'comune', null, 250, 4, 'spazio'],
    ['spa_alieno', 'Alieno amico', 'pet', 'epico', null, 1000, 12, 'spazio'],
    ['spa_aura', 'Aura di pianeti', 'aura', 'raro', null, 600, 9, 'spazio'],
    ['sfondo_luna', 'Base lunare', 'background', 'raro', null, 500, 6, 'spazio'],
    ['cornice_galassia', 'Cornice galattica', 'frame', 'raro', null, 400, 5, 'spazio'],
    ['titolo_set_spazio', 'Esploratore delle stelle', 'title', 'leggendario', null, null, 1],

    // Ninja
    ['nin_fascia', 'Fascia da ninja', 'head', 'comune', null, 200, 3, 'ninja'],
    ['nin_maschera', 'Maschera da ninja', 'face', 'comune', null, 200, 3, 'ninja'],
    ['nin_kimono', 'Kimono', 'torso', 'raro', null, 450, 5, 'ninja'],
    ['nin_pantaloni', 'Pantaloni del dojo', 'legs', 'comune', null, 200, 3, 'ninja'],
    ['nin_calzari', 'Calzari', 'feet', 'comune', null, 200, 3, 'ninja'],
    ['nin_cintura', 'Cintura nera', 'neck', 'raro', null, 400, 6, 'ninja'],
    ['nin_katana', 'Katana', 'handR', 'epico', null, 900, 11, 'ninja'],
    ['nin_stelle', 'Stelle da lancio', 'handL', 'raro', null, 400, 6, 'ninja'],
    ['nin_panda', 'Panda', 'pet', 'epico', null, 1000, 12, 'ninja'],
    ['nin_aura', 'Aura di petali', 'aura', 'raro', null, 600, 9, 'ninja'],
    ['sfondo_tempio', 'Tempio sul monte', 'background', 'raro', null, 500, 6, 'ninja'],
    ['cornice_bambu', 'Cornice di bambù', 'frame', 'raro', null, 400, 5, 'ninja'],
    ['titolo_set_ninja', 'Maestro ninja', 'title', 'leggendario', null, null, 1],

    // ---------------------------------------------------- Temi stagionali: 9a colonna = esclusivo
    // Nascosti finche' l'admin non li apre (Avatar e negozio -> Modifica -> In vendita adesso + scadenza).
    // Natale
    ['nat_cappello', 'Cappello di Natale', 'head', 'raro', null, 200, 1, 'natale', true],
    ['nat_maglione', 'Maglione natalizio', 'torso', 'raro', null, 225, 1, 'natale', true],
    ['nat_pantaloni', 'Pantaloni da elfo', 'legs', 'comune', null, 100, 1, 'natale', true],
    ['nat_stivali', 'Stivali di Natale', 'feet', 'comune', null, 125, 1, 'natale', true],
    ['nat_guanti', 'Muffole', 'hands', 'comune', null, 100, 1, 'natale', true],
    ['nat_sciarpa', 'Sciarpa natalizia', 'neck', 'comune', null, 100, 1, 'natale', true],
    ['nat_bastoncino', 'Bastoncino di zucchero', 'handR', 'raro', null, 200, 1, 'natale', true],
    ['nat_regalo', 'Pacco regalo', 'handL', 'raro', null, 200, 1, 'natale', true],
    ['nat_renna', 'Renna', 'pet', 'epico', null, 500, 1, 'natale', true],
    ['nat_aura', 'Aura di neve', 'aura', 'raro', null, 300, 1, 'natale', true],
    ['sfondo_neve', 'Paesaggio innevato', 'background', 'raro', null, 250, 1, 'natale', true],
    ['cornice_natale', 'Cornice natalizia', 'frame', 'raro', null, 200, 1, 'natale', true],
    ['titolo_set_natale', 'Spirito del Natale', 'title', 'leggendario', null, null, 1],

    // Halloween
    ['hal_cappello', 'Cappello da strega', 'head', 'raro', null, 200, 1, 'halloween', true],
    ['hal_tuta', 'Tuta da scheletro', 'torso', 'raro', null, 225, 1, 'halloween', true],
    ['hal_pantaloni', 'Pantaloni da scheletro', 'legs', 'comune', null, 100, 1, 'halloween', true],
    ['hal_scarpe', 'Scarpe stregate', 'feet', 'comune', null, 100, 1, 'halloween', true],
    ['hal_guanti', 'Mani da mostro', 'hands', 'comune', null, 100, 1, 'halloween', true],
    ['hal_mantello', 'Mantello da vampiro', 'back', 'raro', null, 225, 1, 'halloween', true],
    ['hal_lecca', 'Lecca-lecca stregato', 'handR', 'comune', null, 125, 1, 'halloween', true],
    ['hal_zucca', 'Zucca lanterna', 'handL', 'raro', null, 200, 1, 'halloween', true],
    ['hal_pipistrello', 'Pipistrello', 'pet', 'epico', null, 500, 1, 'halloween', true],
    ['hal_aura', 'Aura spettrale', 'aura', 'raro', null, 300, 1, 'halloween', true],
    ['sfondo_halloween', 'Notte di Halloween', 'background', 'raro', null, 250, 1, 'halloween', true],
    ['cornice_ragnatela', 'Cornice ragnatela', 'frame', 'raro', null, 200, 1, 'halloween', true],
    ['titolo_set_halloween', 'Signore delle zucche', 'title', 'leggendario', null, null, 1],

    // Carnevale
    ['car_cappello', 'Cappello da giullare', 'head', 'raro', null, 200, 1, 'carnevale', true],
    ['car_maschera', 'Maschera di Carnevale', 'face', 'raro', null, 200, 1, 'carnevale', true],
    ['car_costume', 'Costume a rombi', 'torso', 'raro', null, 225, 1, 'carnevale', true],
    ['car_pantaloni', 'Pantaloni colorati', 'legs', 'comune', null, 100, 1, 'carnevale', true],
    ['car_scarpe', 'Scarpe da festa', 'feet', 'comune', null, 100, 1, 'carnevale', true],
    ['car_guanti', 'Guanti da festa', 'hands', 'comune', null, 100, 1, 'carnevale', true],
    ['car_trombetta', 'Trombetta', 'handR', 'comune', null, 125, 1, 'carnevale', true],
    ['car_stelle_filanti', 'Stelle filanti', 'handL', 'comune', null, 125, 1, 'carnevale', true],
    ['car_cagnolino', 'Cagnolino festaiolo', 'pet', 'epico', null, 500, 1, 'carnevale', true],
    ['car_aura', 'Aura di coriandoli', 'aura', 'raro', null, 300, 1, 'carnevale', true],
    ['sfondo_carnevale', 'Festa di Carnevale', 'background', 'raro', null, 250, 1, 'carnevale', true],
    ['cornice_coriandoli', 'Cornice di coriandoli', 'frame', 'raro', null, 200, 1, 'carnevale', true],
    ['titolo_set_carnevale', 'Re del Carnevale', 'title', 'leggendario', null, null, 1],
];
