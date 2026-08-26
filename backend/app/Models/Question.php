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
        'audio_source',
        'itunes_track_id',
        'itunes_track_name',
        'itunes_artist_name',
        'itunes_preview_url',
        'audio_start_seconds',
        'audio_end_seconds',
        'video_path',
        'time_limit_seconds',
    ];

    protected $casts = [
        'audio_start_seconds' => 'float',
        'audio_end_seconds' => 'float',
    ];

    // Relazione: la domanda appartiene a un quiz
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(\App\Models\Answer::class);
    }

    public function resolvedAudioUrl(): ?string
    {
        if ($this->audio_source === 'itunes' && $this->itunes_preview_url) {
            return route('audio.proxy', ['url' => $this->itunes_preview_url]);
        }

        return $this->audio_path ? asset('storage/' . $this->audio_path) : null;
    }
}
