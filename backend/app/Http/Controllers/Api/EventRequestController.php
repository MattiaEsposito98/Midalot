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
            'kind' => ['nullable', 'in:'.implode(',', EventRequest::KINDS)],
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'event_date' => ['nullable', 'date'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        // Campi opzionali assenti dalla richiesta: valida() li omette del
        // tutto dall'array (non li imposta a null), quindi vanno normalizzati
        // qui per evitare "Undefined array key" nella vista dell'email.
        $validated['event_type'] ??= null;
        $validated['event_date'] ??= null;
        $validated['kind'] ??= EventRequest::KIND_EVENTO;

        // Per i servizi digitali la data dell'evento non ha senso.
        if ($validated['kind'] === EventRequest::KIND_SERVIZI) {
            $validated['event_date'] = null;
        }

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
            'message' => $validated['kind'] === EventRequest::KIND_SERVIZI
                ? 'Richiesta inviata! Ti ricontatteremo il prima possibile per parlare del tuo progetto.'
                : 'Richiesta inviata! Ti risponderemo il prima possibile con un preventivo gratuito.',
            'id' => $eventRequest->id,
        ], 201);
    }
}
