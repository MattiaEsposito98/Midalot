<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAnswer extends Model
{
    protected $fillable = [
        'training_attempt_id',
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
        return $this->belongsTo(TrainingAttempt::class, 'training_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}
