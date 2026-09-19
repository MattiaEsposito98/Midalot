<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CookieConsentEvent extends Model
{
    public const EVENTS = ['shown', 'accepted', 'rejected'];

    protected $fillable = [
        'event',
    ];
}
