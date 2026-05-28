<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function attempts()
    {
        return $this->hasMany(TrainingAttempt::class);
    }
}
