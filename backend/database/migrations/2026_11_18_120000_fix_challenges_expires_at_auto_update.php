<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `expires_at` era un TIMESTAMP "NOT NULL" senza default esplicito: MySQL/MariaDB
 * lo creano con ON UPDATE CURRENT_TIMESTAMP, cosi' ogni modifica a una sfida
 * (anche solo lo stato o l'invito) riportava la scadenza a "adesso" e la sfida
 * risultava scaduta. Si toglie l'aggiornamento automatico: la scadenza la
 * imposta solo il codice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || ! Schema::hasTable('challenges')) {
            return;
        }

        $definition = DB::selectOne("SHOW CREATE TABLE `challenges`")->{'Create Table'} ?? '';

        if (stripos($definition, 'ON UPDATE current_timestamp') !== false) {
            DB::statement('ALTER TABLE `challenges` MODIFY `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
    }

    public function down(): void
    {
        // Niente da ripristinare: l'aggiornamento automatico era un difetto.
    }
};
