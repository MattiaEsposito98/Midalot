<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id',
        'user_id',
        'started_at',
        'finished_at',
        'score',
        'completed',
        'total_time',
        'question_order',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_time' => 'integer',
        'question_order' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(\App\Models\QuizAnswer::class, 'attempt_id');
    }
}
