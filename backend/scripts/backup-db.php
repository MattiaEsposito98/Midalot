<?php

/**
 * Dump completo del database in un file .sql, senza bisogno di mysqldump
 * (non disponibile nel chroot dell'hosting).
 *
 * NON si lancia a mano: usare lo script scripts/backup-produzione.sh dal
 * proprio computer, che fa tutto (esecuzione, download, pulizia).
 *
 * Il file prodotto contiene i dati personali di tutti gli utenti: va tenuto
 * fuori da git (la cartella backups/ e' gia' esclusa) e non va condiviso.
 */

// Difesa: questo script non deve mai essere eseguibile via web. La docroot
// punta a public/, quindi non e' raggiungibile, ma se un domani cambiasse
// configurazione un URL come /scripts/backup-db.php scaricherebbe l'intero
// database a chiunque.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Questo file viene caricato ed eseguito direttamente nella root del progetto
// Laravel sul server (non dentro scripts/), quindi __DIR__ e' gia' la root.
$base = __DIR__;

require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$database = DB::getDatabaseName();
$destinazione = $argv[1] ?? $base.'/backup-'.$database.'-'.date('Ymd-His').'.sql';

$fh = fopen($destinazione, 'w');

if (! $fh) {
    fwrite(STDERR, "Impossibile scrivere in {$destinazione}\n");
    exit(1);
}

fwrite($fh, "-- Backup di {$database} del ".date('Y-m-d H:i:s')."\n");
fwrite($fh, "-- Ripristino: mysql -u UTENTE -p NOME_DB < questo_file.sql\n");
fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

$tabelle = array_map(
    fn ($riga) => array_values((array) $riga)[0],
    DB::select('SHOW TABLES')
);

$righeTotali = 0;

foreach ($tabelle as $tabella) {
    $create = (array) DB::selectOne("SHOW CREATE TABLE `{$tabella}`");
    $createSql = $create['Create Table'] ?? array_values($create)[1];

    fwrite($fh, "\n-- Tabella {$tabella}\n");
    fwrite($fh, "DROP TABLE IF EXISTS `{$tabella}`;\n");
    fwrite($fh, $createSql.";\n");

    $righe = DB::table($tabella)->get();
    $righeTotali += $righe->count();

    foreach ($righe->chunk(200) as $blocco) {
        $valori = [];

        foreach ($blocco as $riga) {
            $celle = [];

            foreach ((array) $riga as $valore) {
                if ($valore === null) {
                    $celle[] = 'NULL';
                } elseif (is_int($valore) || is_float($valore)) {
                    $celle[] = $valore;
                } else {
                    $celle[] = DB::connection()->getPdo()->quote((string) $valore);
                }
            }

            $valori[] = '('.implode(',', $celle).')';
        }

        fwrite($fh, "INSERT INTO `{$tabella}` VALUES\n".implode(",\n", $valori).";\n");
    }
}

fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

printf(
    "%d tabelle, %d righe, %.1f KB\n%s\n",
    count($tabelle),
    $righeTotali,
    filesize($destinazione) / 1024,
    $destinazione
);
