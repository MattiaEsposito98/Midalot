<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CookieConsentEvent;
use Illuminate\Http\Request;

class CookieConsentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'in:' . implode(',', CookieConsentEvent::EVENTS)],
        ]);

        CookieConsentEvent::create($validated);

        return response()->json([], 204);
    }
}
