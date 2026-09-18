<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBadgeRun extends Model
{
    protected $fillable = [
        'month',
        'triggered_by',
        'top_score',
    ];

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
