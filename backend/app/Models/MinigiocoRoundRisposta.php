<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinigiocoRoundRisposta extends Model
{
    protected $table = 'minigioco_round_risposte';

    protected $fillable = [
        'attempt_id',
        'round_id',
        'risposta_utente',
        'tentativi_falliti',
        'time_taken',
        'is_correct',
        'is_timeout',
        'score',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'is_timeout' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(MinigiocoAttempt::class, 'attempt_id');
    }

    public function round()
    {
        return $this->belongsTo(MinigiocoRound::class, 'round_id');
    }
}
