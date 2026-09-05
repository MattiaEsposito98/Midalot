<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBadge extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'label',
        'total_score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
