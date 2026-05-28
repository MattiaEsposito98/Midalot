<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAttempt extends Model
{
    protected $fillable = [
        'quiz_id',
        'training_category_id',
        'user_id',
        'question_ids',
        'started_at',
        'finished_at',
        'score',
        'total_time',
        'correct_answers',
        'total_questions',
        'completed',
    ];

    protected $casts = [
        'question_ids' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'completed' => 'boolean',
        'total_time' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function category()
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(TrainingAnswer::class);
    }
}
