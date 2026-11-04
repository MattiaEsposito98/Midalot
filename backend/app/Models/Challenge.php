<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    public const QUESTIONS_COUNT = 7;

    protected $fillable = [
        'challenger_id',
        'opponent_id',
        'mode',
        'status',
        'category_ids',
        'expires_at',
        'completed_at',
        'winner_id',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function challenger()
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function opponent()
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'challenge_questions')
            ->withPivot('position')
            ->orderBy('challenge_questions.position');
    }

    public function attempts()
    {
        return $this->hasMany(ChallengeAttempt::class);
    }

    public function involves(int $userId): bool
    {
        return (int) $this->challenger_id === $userId || (int) $this->opponent_id === $userId;
    }

    public function otherUserId(int $userId): int
    {
        return (int) $this->challenger_id === $userId ? (int) $this->opponent_id : (int) $this->challenger_id;
    }

    /**
     * Le risposte corrette si possono vedere solo a sfida chiusa: finche' un
     * giocatore puo' ancora giocare, rivelarle permetterebbe di passarle
     * all'altro (es. con un secondo account).
     */
    public function isClosed(): bool
    {
        return $this->status !== 'open';
    }
}
