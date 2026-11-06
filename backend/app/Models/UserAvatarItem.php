<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAvatarItem extends Model
{
    protected $fillable = ['user_id', 'avatar_item_id', 'source', 'seen_at'];

    protected $casts = [
        'seen_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(AvatarItem::class, 'avatar_item_id');
    }
}
