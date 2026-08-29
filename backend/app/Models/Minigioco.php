<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Minigioco extends Model
{
    protected $table = 'minigiochi';

    protected $fillable = [
        'title',
        'description',
        'created_by',
        'is_active',
        'leaderboard_visible',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'leaderboard_visible' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rounds()
    {
        return $this->hasMany(MinigiocoRound::class, 'minigioco_id');
    }

    public function attempts()
    {
        return $this->hasMany(MinigiocoAttempt::class, 'minigioco_id');
    }
}
