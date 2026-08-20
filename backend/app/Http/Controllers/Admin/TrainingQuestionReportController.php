<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingQuestionReport;
use Illuminate\Http\Request;

class TrainingQuestionReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'active');

        $reports = TrainingQuestionReport::with(['question', 'quiz', 'resolver'])
            ->when($status === 'active', fn($query) => $query->whereIn('status', [
                TrainingQuestionReport::STATUS_OPEN,
                TrainingQuestionReport::STATUS_IN_PROGRESS,
            ]))
            ->when(in_array($status, TrainingQuestionReport::statuses(), true), fn($query) => $query->where('status', $status))
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'active' => TrainingQuestionReport::whereIn('status', [
                TrainingQuestionReport::STATUS_OPEN,
                TrainingQuestionReport::STATUS_IN_PROGRESS,
            ])->count(),
            'open' => TrainingQuestionReport::where('status', TrainingQuestionReport::STATUS_OPEN)->count(),
            'in_progress' => TrainingQuestionReport::where('status', TrainingQuestionReport::STATUS_IN_PROGRESS)->count(),
            'resolved' => TrainingQuestionReport::where('status', TrainingQuestionReport::STATUS_RESOLVED)->count(),
        ];

        return view('admin.reports.index', compact('reports', 'counts', 'status'));
    }

    public function update(Request $request, TrainingQuestionReport $report)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', TrainingQuestionReport::statuses())],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $isResolved = $validated['status'] === TrainingQuestionReport::STATUS_RESOLVED;
        $wasResolved = $report->status === TrainingQuestionReport::STATUS_RESOLVED;

        $report->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? $report->admin_note,
            'resolved_at' => $isResolved ? ($wasResolved ? $report->resolved_at : now()) : null,
            'resolved_by' => $isResolved ? ($wasResolved ? $report->resolved_by : $request->user()->id) : null,
        ]);

        return back()->with('success', 'Segnalazione aggiornata.');
    }

    public function destroy(TrainingQuestionReport $report)
    {
        $report->delete();

        return back()->with('success', 'Segnalazione eliminata.');
    }
}
