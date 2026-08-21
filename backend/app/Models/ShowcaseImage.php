<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ShowcaseImage extends Model
{
    protected $fillable = [
        'type',
        'image_path',
        'caption',
        'uploaded_by',
    ];

    protected $appends = [
        'image_url',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
