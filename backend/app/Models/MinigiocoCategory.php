<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinigiocoCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function minigiochi()
    {
        return $this->hasMany(Minigioco::class, 'minigioco_category_id');
    }
}
