<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quiz;

class Question extends Model
{
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_AI = 'ai';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';

    /**
     * Le bozze (review_status = pending) sono invisibili a tutto il gioco:
     * Training, One Shot, Midalario, sfide, conteggi. Solo il pannello admin
     * delle domande le vede, con ->withoutGlobalScope('approved').
     */
    protected static function booted(): void
    {
        static::addGlobalScope('approved', function (Builder $query) {
            $query->where($query->getModel()->getTable() . '.review_status', self::STATUS_APPROVED);
        });
    }

    public function isPending(): bool
    {
        return $this->review_status === self::STATUS_PENDING;
    }

    public function isAi(): bool
    {
        return $this->source === self::SOURCE_AI;
    }

    protected $fillable = [
        'quiz_id',
        'source',
        'review_status',
        'ai_note',
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
