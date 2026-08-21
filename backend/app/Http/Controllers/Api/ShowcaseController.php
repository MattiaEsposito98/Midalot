<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShowcaseImage;

class ShowcaseController extends Controller
{
    public function index()
    {
        return response()->json([
            'testimonials' => $this->images('testimonial'),
            'collabs' => $this->images('collab'),
            'feedbacks' => $this->images('feedback'),
        ]);
    }

    private function images(string $type)
    {
        return ShowcaseImage::where('type', $type)
            ->latest()
            ->get(['id', 'caption', 'image_path', 'created_at'])
            ->map(fn ($image) => [
                'id' => $image->id,
                'caption' => $image->caption,
                'url' => $image->image_url,
                'created_at' => $image->created_at,
            ]);
    }
}
