<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'description',
        'created_by',
        'is_active',
        'leaderboard_visible'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'leaderboard_visible' => 'boolean',
    ];

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
}
