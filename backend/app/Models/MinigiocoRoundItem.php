<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinigiocoRoundItem extends Model
{
    protected $fillable = [
        'minigioco_round_id',
        'ordine',
        'label',
        'image_path',
        'is_intruso',
    ];

    protected $casts = [
        'is_intruso' => 'boolean',
        'ordine' => 'integer',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function round()
    {
        return $this->belongsTo(MinigiocoRound::class, 'minigioco_round_id');
    }
}
