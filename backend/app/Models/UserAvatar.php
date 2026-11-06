<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAvatar extends Model
{
    protected $fillable = ['user_id', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
