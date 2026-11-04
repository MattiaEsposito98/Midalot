<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeAttempt extends Model
{
    protected $fillable = [
        'challenge_id',
        'user_id',
        'started_at',
        'finished_at',
        'score',
        'total_time',
        'correct_answers',
        'completed',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'completed' => 'boolean',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(ChallengeAnswer::class);
    }
}
