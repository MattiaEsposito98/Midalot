<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeParticipant extends Model
{
    protected $fillable = [
        'challenge_id',
        'user_id',
        'status',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ancora in gioco: invitato che puo' ancora giocare o gia' accettato.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['invited', 'accepted'], true);
    }
}
