<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Quiz;

class Question extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_text',
        'image_path',
        'audio_path',
        'video_path',
        'time_limit_seconds',
        'order',
    ];

    // Relazione: la domanda appartiene a un quiz
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
