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

    protected $appends = [
        'badges',
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

    public function minigiocoAttempts()
    {
        return $this->hasMany(\App\Models\MinigiocoAttempt::class);
    }

    public function logins()
    {
        return $this->hasMany(\App\Models\UserLogin::class);
    }

    public function monthlyBadges()
    {
        return $this->hasMany(\App\Models\MonthlyBadge::class);
    }

    public function latestMonthlyBadge()
    {
        return $this->hasOne(\App\Models\MonthlyBadge::class)->latestOfMany('month');
    }

    public function midalarioBadges()
    {
        return $this->hasMany(\App\Models\MidalarioBadge::class);
    }

    /**
     * Tutti i badge dell'utente (mensile + Midalario), per mostrarli
     * affiancati ovunque nel sito. Vuoto se le relazioni non sono state
     * caricate (nessuna query aggiuntiva "a sorpresa" nei contesti bulk
     * che non le richiedono esplicitamente).
     */
    public function getBadgesAttribute(): array
    {
        if (! $this->relationLoaded('latestMonthlyBadge') && ! $this->relationLoaded('midalarioBadges')) {
            return [];
        }

        $badges = [];

        if ($this->relationLoaded('latestMonthlyBadge') && $this->latestMonthlyBadge) {
            $badges[] = [
                'type' => 'monthly',
                'label' => $this->latestMonthlyBadge->label,
            ];
        }

        if ($this->relationLoaded('midalarioBadges')) {
            foreach ($this->midalarioBadges as $badge) {
                $badges[] = [
                    'type' => 'midalario',
                    'label' => $badge->label,
                ];
            }
        }

        return $badges;
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
