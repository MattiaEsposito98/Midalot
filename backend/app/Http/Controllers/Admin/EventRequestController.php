<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventRequest;
use Illuminate\Http\Request;

class EventRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'active');

        $eventRequests = EventRequest::with(['user', 'handler'])
            ->when($status === 'active', fn($query) => $query->whereIn('status', [
                EventRequest::STATUS_NEW,
                EventRequest::STATUS_CONTACTED,
            ]))
            ->when(in_array($status, EventRequest::statuses(), true), fn($query) => $query->where('status', $status))
            ->orderByRaw("CASE status WHEN 'new' THEN 1 WHEN 'contacted' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'active' => EventRequest::whereIn('status', [
                EventRequest::STATUS_NEW,
                EventRequest::STATUS_CONTACTED,
            ])->count(),
            'new' => EventRequest::where('status', EventRequest::STATUS_NEW)->count(),
            'contacted' => EventRequest::where('status', EventRequest::STATUS_CONTACTED)->count(),
            'closed' => EventRequest::where('status', EventRequest::STATUS_CLOSED)->count(),
        ];

        return view('admin.event-requests.index', compact('eventRequests', 'counts', 'status'));
    }

    public function update(Request $request, EventRequest $eventRequest)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', EventRequest::statuses())],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $isHandled = $validated['status'] !== EventRequest::STATUS_NEW;
        $wasHandled = $eventRequest->status !== EventRequest::STATUS_NEW;

        $eventRequest->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? $eventRequest->admin_note,
            'handled_at' => $isHandled ? ($wasHandled ? $eventRequest->handled_at : now()) : null,
            'handled_by' => $isHandled ? ($wasHandled ? $eventRequest->handled_by : $request->user()->id) : null,
        ]);

        return back()->with('success', 'Richiesta aggiornata.');
    }

    public function destroy(EventRequest $eventRequest)
    {
        $eventRequest->delete();

        return back()->with('success', 'Richiesta eliminata.');
    }
}
