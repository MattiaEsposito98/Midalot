<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProspectSearch extends Model
{
    public const STATUSES = [
        'planned' => 'Da fare',
        'in_progress' => 'In corso',
        'done' => 'Fatta',
    ];

    protected $fillable = ['name', 'region', 'city', 'sector', 'status', 'searched_at', 'notes'];

    protected $casts = ['searched_at' => 'date'];

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }
}
