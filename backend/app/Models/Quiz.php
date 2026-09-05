<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'type',
        'restrict_to_specific_users',
        'training_category_id',
        'training_subcategory_id',
        'training_question_mode',
        'created_by',
        'is_active',
        'leaderboard_visible',
        'midalario_status',
        'midalario_started_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'restrict_to_specific_users' => 'boolean',
        'leaderboard_visible' => 'boolean',
        'midalario_started_at' => 'datetime',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function isTraining(): bool
    {
        return $this->type === 'training';
    }

    public function isMidalario(): bool
    {
        return $this->type === 'midalario';
    }

    // Relazione: chi ha creato il quiz (admin)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // In futuro collegheremo le domande
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function trainingCategory()
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    public function trainingSubcategory()
    {
        return $this->belongsTo(TrainingSubcategory::class, 'training_subcategory_id');
    }

    public function trainingAttempts()
    {
        return $this->hasMany(TrainingAttempt::class);
    }

    public function participants()
    {
        return $this->hasMany(QuizParticipant::class);
    }
}
