<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvatarItem extends Model
{
    public const SLOTS = [
        'head', 'face', 'torso', 'armor', 'neck', 'hands', 'legs', 'feet',
        'handR', 'handL', 'back', 'pet', 'aura', 'background', 'title', 'frame',
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
        'is_exclusive',
        'exclusive_open',
        'exclusive_until',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'unlock_level' => 'integer',
        'price' => 'integer',
        'shop_min_level' => 'integer',
        'is_exclusive' => 'boolean',
        'exclusive_open' => 'boolean',
        'exclusive_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Esclusivo in vendita adesso: aperto dall'admin e non scaduto.
     */
    public function exclusiveOnSale(): bool
    {
        return $this->is_exclusive
            && $this->exclusive_open
            && ($this->exclusive_until === null || $this->exclusive_until->isFuture());
    }

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
