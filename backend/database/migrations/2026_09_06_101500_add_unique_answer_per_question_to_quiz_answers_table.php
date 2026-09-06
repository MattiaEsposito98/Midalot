<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Senza questo indice il controllo "ho gia' risposto?" nel controller non e'
 * atomico: piu' richieste parallele sulla stessa domanda superano tutte il
 * controllo prima che una venga scritta, moltiplicando il punteggio.
 * training_answers e minigioco_round_risposte avevano gia' il vincolo,
 * quiz_answers (Quiz One Shot e Midalario) no.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->unique(['attempt_id', 'question_id'], 'quiz_answers_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropUnique('quiz_answers_attempt_question_unique');
        });
    }
};
