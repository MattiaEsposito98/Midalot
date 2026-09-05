<?php

namespace App\Models;

use App\Services\KeyboardShiftCipher;
use Illuminate\Database\Eloquent\Model;

class MinigiocoRound extends Model
{
    protected $table = 'minigioco_round';

    protected $fillable = [
        'minigioco_id',
        'parola_originale',
        'shift',
        'time_limit_seconds',
        'content_mode',
    ];

    public function minigioco()
    {
        return $this->belongsTo(Minigioco::class, 'minigioco_id');
    }

    public function risposte()
    {
        return $this->hasMany(MinigiocoRoundRisposta::class, 'round_id');
    }

    public function items()
    {
        return $this->hasMany(MinigiocoRoundItem::class, 'minigioco_round_id')->orderBy('ordine');
    }

    public function getParolaCifrataAttribute(): string
    {
        return KeyboardShiftCipher::encode($this->parola_originale, $this->shift);
    }
}
