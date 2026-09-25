<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizParticipant extends Model
{
    protected $fillable = [
        'quiz_id',
        'user_id',
        'ip_address',
        'room_entered_at',
    ];

    protected $casts = [
        'room_entered_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
