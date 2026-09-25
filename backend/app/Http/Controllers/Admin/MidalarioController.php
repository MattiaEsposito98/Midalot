<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\MidalarioFinalizer;
use App\Services\MidalarioTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MidalarioController extends Controller
{
    /**
     * Margine tra il clic su "Avvia" e l'inizio effettivo della prima domanda.
     * I giocatori interrogano il server ogni 2 secondi: con un margine di soli
     * 5 secondi il primo sondaggio utile puo' arrivare quando ne restano gia'
     * 3, mostrando un conto alla rovescia incompleto. 7 secondi garantiscono a
     * tutti di vedere l'intero "5, 4, 3, 2, 1" anche nel caso peggiore.
     */
    public const SECONDI_DI_ATTESA_PRIMA_DEL_VIA = 7;

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

        // Il cronometro parte qualche secondo nel futuro: quel margine serve al
        // "3, 2, 1" nella pagina dei giocatori. Senza, il conto alla rovescia
        // mangerebbe i primi secondi della prima domanda.
        $startedAt = now()->addSeconds(self::SECONDI_DI_ATTESA_PRIMA_DEL_VIA);
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
                    'user_id' => $participant->user_id,
                    'nickname' => $participant->user->nickname ?? $participant->user->email ?? 'Utente',
                    'badge' => $participant->user->latestMonthlyBadge?->label,
                    'joined_at' => $participant->created_at,
                    'ip_address' => $participant->ip_address,
                    'room_entered_at' => $participant->room_entered_at,
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

    public function participantAnswers(Quiz $quiz, User $user)
    {
        $this->ensureMidalario($quiz);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        $quiz->load([
            'questions' => function ($q) {
                $q->orderBy('id')->select(
                    'id', 'quiz_id', 'question_text',
                    'image_path', 'audio_path', 'audio_source', 'itunes_preview_url', 'video_path',
                );
            },
            'questions.answers:id,question_id,answer_text,is_correct',
        ]);

        $givenAnswers = $attempt
            ? QuizAnswer::where('attempt_id', $attempt->id)->get()->keyBy('question_id')
            : collect();

        $orderedQuestions = $quiz->questions;

        if ($attempt && ! empty($attempt->question_order)) {
            $questionsById = $quiz->questions->keyBy('id');

            $orderedQuestions = collect($attempt->question_order)
                ->map(fn ($questionId) => $questionsById->get($questionId))
                ->filter()
                ->values();
        }

        $rows = $orderedQuestions->map(function ($question) use ($givenAnswers) {
            $given = $givenAnswers->get($question->id);
            $correctAnswer = $question->answers->firstWhere('is_correct', true);
            $givenAnswer = $given?->answer_id
                ? $question->answers->firstWhere('id', $given->answer_id)
                : null;

            return [
                'question_text' => $question->question_text,
                'given_answer_text' => $givenAnswer?->answer_text,
                'correct_answer_text' => $correctAnswer?->answer_text,
                'is_correct' => (bool) ($given?->is_correct ?? false),
                'is_wrong' => (bool) ($given?->is_wrong ?? false),
                'is_timeout' => (bool) ($given?->is_timeout ?? ! $given),
                'time_taken' => $given?->time_taken,
                'score' => $given?->score ?? 0,
            ];
        });

        return view('admin.midalario.participant-answers', [
            'quiz' => $quiz,
            'user' => $user,
            'attempt' => $attempt,
            'rows' => $rows,
        ]);
    }

    public function pdfData(Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        $questions = $quiz->questions()
            ->orderBy('id')
            ->with('answers:id,question_id,answer_text')
            ->get()
            ->map(function ($question) {
                $hasAudio = (bool) ($question->audio_path || $question->itunes_preview_url);

                return [
                    'question_text' => $question->question_text,
                    'has_image' => (bool) $question->image_path,
                    'image_url' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                    'has_audio' => $hasAudio,
                    'has_video' => (bool) $question->video_path,
                    'answers' => $question->answers->pluck('answer_text')->values(),
                ];
            });

        return response()->json([
            'title' => $quiz->title,
            'questions' => $questions,
        ]);
    }

    private function validateMidalario(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'midalario_scheduled_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
    }

    private function ensureMidalario(Quiz $quiz): void
    {
        abort_unless($quiz->type === 'midalario', 404);
    }
}
