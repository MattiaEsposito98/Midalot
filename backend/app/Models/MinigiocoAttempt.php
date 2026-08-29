<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinigiocoAttempt extends Model
{
    protected $fillable = [
        'minigioco_id',
        'user_id',
        'started_at',
        'finished_at',
        'score',
        'total_time',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_time' => 'integer',
    ];

    public function minigioco()
    {
        return $this->belongsTo(Minigioco::class, 'minigioco_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function risposte()
    {
        return $this->hasMany(MinigiocoRoundRisposta::class, 'attempt_id');
    }

    /**
     * Chiude definitivamente un tentativo interrotto, salvando il
     * punteggio accumulato fino a quel momento. Il tentativo non è
     * più riprendibile.
     */
    public function finalizeWithAccumulatedScore(): void
    {
        $this->update([
            'score' => $this->risposte()->sum('score'),
            'total_time' => $this->risposte()->sum('time_taken'),
            'completed' => true,
            'finished_at' => $this->finished_at ?? now(),
        ]);
    }
}
