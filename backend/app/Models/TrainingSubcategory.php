<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSubcategory extends Model
{
    protected $fillable = [
        'training_category_id',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}
