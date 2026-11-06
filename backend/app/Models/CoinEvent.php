<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinEvent extends Model
{
    protected $fillable = ['user_id', 'source', 'source_id', 'amount'];
}
