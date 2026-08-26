<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ITunesController extends Controller
{
    public function search(Request $request)
    {
        $term = trim((string) $request->get('term', ''));

        if ($term === '') {
            return response()->json(['results' => []]);
        }

        $response = Http::timeout(6)->get('https://itunes.apple.com/search', [
            'term' => $term,
            'media' => 'music',
            'entity' => 'song',
            'limit' => 15,
            'country' => 'IT',
        ]);

        if (! $response->ok()) {
            return response()->json(['results' => []], 502);
        }

        $results = collect($response->json('results', []))
            ->filter(fn ($track) => ! empty($track['previewUrl']))
            ->map(fn ($track) => [
                'trackId' => $track['trackId'] ?? null,
                'trackName' => $track['trackName'] ?? '',
                'artistName' => $track['artistName'] ?? '',
                'previewUrl' => $track['previewUrl'] ?? null,
                'artworkUrl' => $track['artworkUrl60'] ?? $track['artworkUrl30'] ?? null,
            ])
            ->values();

        return response()->json(['results' => $results]);
    }
}
