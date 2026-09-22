<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EventRequestMail;
use App\Models\EventRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EventRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'event_date' => ['nullable', 'date'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        $user = Auth::guard('sanctum')->user();

        $eventRequest = EventRequest::create([
            ...$validated,
            'user_id' => $user?->id,
            'status' => EventRequest::STATUS_NEW,
        ]);

        Mail::to(config('mail.reports_to'))->send(new EventRequestMail([
            ...$validated,
            'user_email' => $user?->email,
        ]));

        return response()->json([
            'message' => 'Richiesta inviata! Ti risponderemo il prima possibile con un preventivo gratuito.',
            'id' => $eventRequest->id,
        ], 201);
    }
}
