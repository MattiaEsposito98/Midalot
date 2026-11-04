<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeAnswer extends Model
{
    protected $fillable = [
        'challenge_attempt_id',
        'question_id',
        'answer_id',
        'time_taken',
        'is_correct',
        'is_timeout',
        'is_wrong',
        'score',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'is_timeout' => 'boolean',
        'is_wrong' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(ChallengeAttempt::class, 'challenge_attempt_id');
    }
}
