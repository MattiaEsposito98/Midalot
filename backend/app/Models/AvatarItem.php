<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvatarItem extends Model
{
    public const SLOTS = [
        'head', 'face', 'torso', 'armor', 'neck', 'hands', 'legs', 'feet',
        'handR', 'handL', 'back', 'pet', 'aura', 'background',
    ];

    // Slot che non possono restare vuoti (si torna al vestito base).
    public const REQUIRED_SLOTS = ['torso', 'legs', 'feet'];

    protected $fillable = [
        'key',
        'name',
        'slot',
        'rarity',
        'unlock_level',
        'price',
        'shop_min_level',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'unlock_level' => 'integer',
        'price' => 'integer',
        'shop_min_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function toCatalog(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'slot' => $this->slot,
            'rarity' => $this->rarity,
            'unlock_level' => $this->unlock_level,
            'price' => $this->price,
            'shop_min_level' => $this->shop_min_level,
        ];
    }
}
