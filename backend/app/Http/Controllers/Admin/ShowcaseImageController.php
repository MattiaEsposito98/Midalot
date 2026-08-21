<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShowcaseImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShowcaseImageController extends Controller
{
    public function index()
    {
        $testimonials = ShowcaseImage::where('type', 'testimonial')
            ->latest()
            ->get();

        $collabs = ShowcaseImage::where('type', 'collab')
            ->latest()
            ->get();

        $feedbacks = ShowcaseImage::where('type', 'feedback')
            ->latest()
            ->get();

        return view('admin.showcase.index', compact('testimonials', 'collabs', 'feedbacks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:testimonial,collab,feedback'],
            'caption' => ['nullable', 'string', 'max:120'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        ShowcaseImage::create([
            'type' => $validated['type'],
            'caption' => $validated['caption'] ?? null,
            'image_path' => $request->file('image')->store('showcase', 'public'),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Immagine caricata.');
    }

    public function destroy(ShowcaseImage $showcase)
    {
        Storage::disk('public')->delete($showcase->image_path);
        $showcase->delete();

        return back()->with('success', 'Immagine eliminata.');
    }
}
