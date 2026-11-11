<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Partita di un ospite (non registrato) a una sfida ricevuta con un link.
 * Le risposte stanno in `answers` finche' l'ospite non si registra: a quel
 * punto diventano un normale tentativo del suo account (ChallengeService::claimGuest).
 */
class ChallengeGuestAttempt extends Model
{
    protected $fillable = [
        'challenge_id',
        'guest_key',
        'started_at',
        'finished_at',
        'score',
        'correct_answers',
        'total_time',
        'answers',
        'claimed_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'answers' => 'array',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
}
