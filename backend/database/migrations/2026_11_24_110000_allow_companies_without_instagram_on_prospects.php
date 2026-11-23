<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Clienti da contattare" accoglie anche piccole aziende trovate fuori da Instagram (contatto via email o telefono):
     * il profilo Instagram diventa facoltativo (l'indice unico resta: piu' righe senza profilo sono ammesse)
     * e si aggiunge "need" = di cosa potrebbe avere bisogno (prenotazioni, preventivi, iscritti...).
     */
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('instagram_handle', 60)->nullable()->change();
            $table->string('need', 200)->nullable()->after('proposal');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('need');
        });
        // Il profilo resta facoltativo: non si puo' tornare a "obbligatorio" se esistono righe senza profilo.
    }
};
