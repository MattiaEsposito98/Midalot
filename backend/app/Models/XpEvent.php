<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XpEvent extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'source_id',
        'xp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
