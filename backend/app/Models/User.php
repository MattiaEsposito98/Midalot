<?php

namespace App\Models;

use App\Mail\VerifyEmailMail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'phone',
        'password',
        'birth_date',
        'city_id',
        'privacy_accepted_at',
        'terms_accepted_at',
        'rules_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'privacy_accepted_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'rules_accepted_at' => 'datetime',
        ];
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class)->withTimestamps();
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(\App\Models\QuizAttempt::class);
    }

    public function trainingAttempts()
    {
        return $this->hasMany(\App\Models\TrainingAttempt::class);
    }

    public function logins()
    {
        return $this->hasMany(\App\Models\UserLogin::class);
    }

    public function sendEmailVerificationNotification()
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $this->id,
                'hash' => sha1($this->email),
            ]
        );

        Mail::to($this->email)->send(new VerifyEmailMail($url, $this));
    }
}
