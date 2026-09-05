<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\MidalarioFinalizer;
use App\Services\MidalarioTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MidalarioController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::where('type', 'midalario')
            ->withCount(['questions', 'participants'])
            ->latest()
            ->get();

        return view('admin.midalario.index', compact('quizzes'));
    }

    public function create()
    {
        return view('admin.midalario.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateMidalario($request);

        $data = [
            ...$validated,
            'type' => 'midalario',
            'created_by' => Auth::id(),
            'midalario_status' => 'open',
            'leaderboard_visible' => true,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('covers', 'public');
        }

        Quiz::create($data);

        return redirect()
            ->route('admin.midalario.index')
            ->with('success', 'Quiz Midalario creato.');
    }

    public function edit(Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        return view('admin.midalario.edit', compact('quiz'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        $data = $this->validateMidalario($request);

        if ($request->has('remove_image') && $quiz->image_path) {
            Storage::disk('public')->delete($quiz->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($quiz->image_path) {
                Storage::disk('public')->delete($quiz->image_path);
            }

            $data['image_path'] = $request->file('image')->store('covers', 'public');
        }

        $quiz->update($data);

        return redirect()
            ->route('admin.midalario.index')
            ->with('success', 'Quiz Midalario aggiornato.');
    }

    public function destroy(Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        if ($quiz->image_path) {
            Storage::disk('public')->delete($quiz->image_path);
        }

        $quiz->delete();

        return redirect()
            ->route('admin.midalario.index')
            ->with('success', 'Quiz Midalario eliminato.');
    }

    public function closeParticipation(Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        if ($quiz->midalario_status !== 'open') {
            return back()->with('error', 'Le iscrizioni non sono aperte.');
        }

        $quiz->update(['midalario_status' => 'closed']);

        return back()->with('success', 'Iscrizioni chiuse. Puoi avviare il quiz quando vuoi.');
    }

    public function reopenParticipation(Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        if ($quiz->midalario_status !== 'closed') {
            return back()->with('error', 'Le iscrizioni non sono chiuse.');
        }

        $quiz->update(['midalario_status' => 'open']);

        return back()->with('success', 'Iscrizioni riaperte.');
    }

    public function start(Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        if ($quiz->midalario_status !== 'closed') {
            return back()->with('error', 'Devi prima chiudere le iscrizioni.');
        }

        if ($quiz->questions()->count() === 0) {
            return back()->with('error', 'Aggiungi almeno una domanda prima di avviare il quiz.');
        }

        $startedAt = now();
        $questionIds = $quiz->questions()->orderBy('id')->pluck('id')->all();

        foreach ($quiz->participants as $participant) {
            $shuffledQuestionIds = $questionIds;
            shuffle($shuffledQuestionIds);

            QuizAttempt::firstOrCreate(
                ['quiz_id' => $quiz->id, 'user_id' => $participant->user_id],
                [
                    'started_at' => $startedAt,
                    'completed' => false,
                    'score' => 0,
                    'question_order' => $shuffledQuestionIds,
                ]
            );
        }

        $quiz->update([
            'midalario_status' => 'running',
            'midalario_started_at' => $startedAt,
        ]);

        return back()->with('success', 'Quiz avviato per tutti i partecipanti in sala.');
    }

    public function monitor(Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);
        $quiz->refresh();

        $timeline = new MidalarioTimeline($quiz);
        $totalQuestions = $timeline->questions()->count();

        $window = $quiz->midalario_status === 'running' ? $timeline->currentWindow() : null;

        $participants = $quiz->participants()
            ->with(['user:id,nickname,email', 'user.latestMonthlyBadge'])
            ->get()
            ->map(function ($participant) use ($quiz, $window) {
                $attempt = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('user_id', $participant->user_id)
                    ->first();

                $answeredCount = $attempt
                    ? QuizAnswer::where('attempt_id', $attempt->id)->count()
                    : 0;

                $hasAnsweredCurrent = false;

                if ($attempt && $window) {
                    $questionId = $attempt->question_order[$window['index']] ?? $window['question']->id;

                    $hasAnsweredCurrent = QuizAnswer::where('attempt_id', $attempt->id)
                        ->where('question_id', $questionId)
                        ->exists();
                }

                return [
                    'nickname' => $participant->user->nickname ?? $participant->user->email ?? 'Utente',
                    'badge' => $participant->user->latestMonthlyBadge?->label,
                    'joined_at' => $participant->created_at,
                    'answered_count' => $answeredCount,
                    'has_answered_current' => $hasAnsweredCurrent,
                    'completed' => (bool) ($attempt?->completed ?? false),
                    'score' => $attempt?->completed ? $attempt->score : null,
                ];
            });

        return view('admin.midalario.monitor', [
            'quiz' => $quiz,
            'participants' => $participants,
            'totalQuestions' => $totalQuestions,
            'currentQuestionIndex' => $window['index'] ?? null,
        ]);
    }

    private function validateMidalario(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
    }

    private function ensureMidalario(Quiz $quiz): void
    {
        abort_unless($quiz->type === 'midalario', 404);
    }
}
