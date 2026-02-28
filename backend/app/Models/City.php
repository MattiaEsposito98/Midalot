<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'name',
        'province',
        'region',
        'country',
        'country_code',
        'latitude',
        'longitude',
        'istat_code',
    ];
}
