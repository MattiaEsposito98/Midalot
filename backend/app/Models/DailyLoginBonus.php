<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyLoginBonus extends Model
{
    protected $fillable = [
        'user_id',
        'bonus_date',
        'score',
    ];

    protected $casts = [
        'bonus_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
