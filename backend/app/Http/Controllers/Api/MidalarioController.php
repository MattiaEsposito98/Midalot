<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Services\MidalarioFinalizer;
use App\Services\MidalarioTimeline;
use Illuminate\Http\Request;

class MidalarioController extends Controller
{
    private const CORRECT_ANSWER_BASE_SCORE = 7000;

    private const MAX_SPEED_BONUS = 3000;

    private const WRONG_ANSWER_PENALTY_RATE = 0.10;

    public function announcement()
    {
        $quiz = Quiz::where('type', 'midalario')
            ->where('is_active', true)
            ->whereIn('midalario_status', ['open', 'closed', 'running'])
            ->latest()
            ->first();

        if (! $quiz) {
            return response()->json(['quiz' => null]);
        }

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'status' => $quiz->midalario_status,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $quizzes = Quiz::where('type', 'midalario')
            ->where('is_active', true)
            ->withCount('participants')
            ->withCount('questions')
            ->latest()
            ->get()
            ->map(function ($quiz) use ($user) {
                $attempt = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'image' => $quiz->image_url,
                    'status' => $quiz->midalario_status,
                    'questions_count' => $quiz->questions_count,
                    'participants_count' => $quiz->participants_count,
                    'joined' => $quiz->participants()->where('user_id', $user->id)->exists(),
                    'completed' => (bool) ($attempt?->completed ?? false),
                    'score' => $attempt?->completed ? $attempt->score : null,
                ];
            });

        return response()->json(['quizzes' => $quizzes]);
    }

    public function join(Request $request, Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        $user = $request->user();

        if (! $quiz->is_active || $quiz->midalario_status !== 'open') {
            return response()->json([
                'message' => 'Le iscrizioni per questo quiz non sono aperte',
            ], 403);
        }

        QuizParticipant::firstOrCreate([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Iscrizione confermata, sei in sala d\'attesa',
        ]);
    }

    public function status(Request $request, Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        $user = $request->user();

        if ($quiz->midalario_status === 'running') {
            (new MidalarioFinalizer())->finalizeIfNeeded($quiz);
            $quiz->refresh();
        }

        $timeline = new MidalarioTimeline($quiz);
        $isParticipant = $quiz->participants()->where('user_id', $user->id)->exists();

        $base = [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
            ],
            'status' => $quiz->midalario_status,
            'server_time' => now()->toISOString(),
            'participants_count' => $quiz->participants()->count(),
            'total_questions' => $timeline->questions()->count(),
        ];

        if (! $isParticipant) {
            return response()->json([...$base, 'joined' => false]);
        }

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        $payload = [
            ...$base,
            'joined' => true,
            'completed' => (bool) ($attempt?->completed ?? false),
            'score' => $attempt?->completed ? $attempt->score : null,
        ];

        if ($quiz->midalario_status === 'running' && $attempt && ! $attempt->completed) {
            $window = $timeline->currentWindow();

            if ($window) {
                $question = $this->resolveQuestionForAttempt($attempt, $window, $timeline);

                $payload['question'] = [
                    'index' => $window['index'],
                    'total_questions' => $timeline->questions()->count(),
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'image' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                    'audio' => $question->resolvedAudioUrl(),
                    'audio_start_seconds' => $question->audio_start_seconds,
                    'audio_end_seconds' => $question->audio_end_seconds,
                    'video' => $question->video_path ? asset('storage/'.$question->video_path) : null,
                    'starts_at' => $window['starts_at']->toISOString(),
                    'ends_at' => $window['ends_at']->toISOString(),
                    'answers' => $question->answers()->get(['id', 'answer_text']),
                ];

                $payload['has_answered'] = QuizAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->exists();
            }
        }

        return response()->json($payload);
    }

    public function answer(Request $request, Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'required|exists:answers,id',
        ]);

        $user = $request->user();

        if ($quiz->midalario_status !== 'running') {
            return response()->json(['message' => 'Il quiz non è in corso'], 403);
        }

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $attempt || $attempt->completed) {
            return response()->json(['message' => 'Tentativo non valido'], 403);
        }

        $timeline = new MidalarioTimeline($quiz);
        $window = $timeline->currentWindow();

        if (! $window) {
            return response()->json(['message' => 'Questa domanda non è più attiva'], 422);
        }

        $question = $this->resolveQuestionForAttempt($attempt, $window, $timeline);

        if ((int) $question->id !== (int) $request->question_id) {
            return response()->json(['message' => 'Questa domanda non è più attiva'], 422);
        }

        $alreadyAnswered = QuizAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['message' => 'Hai già risposto a questa domanda'], 403);
        }

        $answer = Answer::where('id', $request->answer_id)
            ->where('question_id', $question->id)
            ->first();

        if (! $answer) {
            return response()->json(['message' => 'Risposta non valida'], 422);
        }

        $maxTimeMs = max(1, (int) $window['starts_at']->diffInMilliseconds($window['ends_at']));
        $timeTakenMs = min($maxTimeMs, max(0, $window['starts_at']->diffInMilliseconds(now())));

        $isCorrect = (bool) $answer->is_correct;
        $currentScore = max(0, (int) QuizAnswer::where('attempt_id', $attempt->id)->sum('score'));

        if ($isCorrect) {
            $speedBonus = (int) round((($maxTimeMs - $timeTakenMs) / $maxTimeMs) * self::MAX_SPEED_BONUS);
            $score = self::CORRECT_ANSWER_BASE_SCORE + $speedBonus;
        } else {
            $score = -$this->calculatePenalty($currentScore, self::WRONG_ANSWER_PENALTY_RATE);
        }

        QuizAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_id' => $answer->id,
            'time_taken' => $timeTakenMs,
            'is_correct' => $isCorrect,
            'is_timeout' => false,
            'is_wrong' => ! $isCorrect,
            'score' => $score,
        ]);

        return response()->json([
            'correct' => $isCorrect,
            'score' => $score,
        ]);
    }

    public function review(Request $request, Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        $user = $request->user();

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->first();

        if (! $attempt) {
            return response()->json([
                'message' => 'Devi completare il quiz prima di poter vedere il riepilogo',
            ], 403);
        }

        $quiz->load([
            'questions' => function ($q) {
                $q->orderBy('id')
                    ->select(
                        'id',
                        'quiz_id',
                        'question_text',
                        'image_path',
                        'audio_path',
                        'audio_source',
                        'itunes_preview_url',
                        'audio_start_seconds',
                        'audio_end_seconds',
                        'video_path',
                        'time_limit_seconds'
                    );
            },
            'questions.answers:id,question_id,answer_text,is_correct',
        ]);

        $givenAnswers = QuizAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $orderedQuestions = $quiz->questions;

        if (! empty($attempt->question_order)) {
            $questionsById = $quiz->questions->keyBy('id');

            $orderedQuestions = collect($attempt->question_order)
                ->map(fn ($questionId) => $questionsById->get($questionId))
                ->filter()
                ->values();
        }

        $questions = $orderedQuestions->map(function ($question) use ($givenAnswers) {
            $given = $givenAnswers->get($question->id);
            $correctAnswer = $question->answers->firstWhere('is_correct', true);
            $givenAnswer = $given?->answer_id
                ? $question->answers->firstWhere('id', $given->answer_id)
                : null;

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'image' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                'audio' => $question->resolvedAudioUrl(),
                'audio_start_seconds' => $question->audio_start_seconds,
                'audio_end_seconds' => $question->audio_end_seconds,
                'video' => $question->video_path ? asset('storage/'.$question->video_path) : null,
                'time_limit_seconds' => $question->time_limit_seconds,
                'given_answer_text' => $givenAnswer?->answer_text,
                'correct_answer_text' => $correctAnswer?->answer_text,
                'is_correct' => (bool) ($given?->is_correct ?? false),
                'is_wrong' => (bool) ($given?->is_wrong ?? false),
                'is_timeout' => (bool) ($given?->is_timeout ?? false),
                'time_taken' => $given?->time_taken,
                'score' => $given?->score ?? 0,
            ];
        });

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
            ],
            'score' => $attempt->score,
            'total_time' => $attempt->total_time,
            'finished_at' => $attempt->finished_at,
            'questions' => $questions,
        ]);
    }

    public function leaderboard(Request $request, Quiz $quiz)
    {
        $this->ensureMidalario($quiz);

        $user = $request->user();

        $isParticipant = $quiz->participants()->where('user_id', $user->id)->exists();

        if (! $isParticipant) {
            return response()->json(['message' => 'Non hai partecipato a questo quiz'], 403);
        }

        $totalQuestions = $quiz->questions()->count();

        $attempts = QuizAttempt::with(['user', 'answers'])
            ->where('quiz_id', $quiz->id)
            ->get();

        $results = $attempts->map(function ($attempt) use ($totalQuestions) {
            $correct = $attempt->answers?->where('is_correct', true)->count() ?? 0;

            return [
                'user' => [
                    'nickname' => $attempt->user->nickname ?? 'Utente',
                ],
                'score' => $attempt->score ?? 0,
                'correct_answers' => $correct,
                'total_questions' => $totalQuestions,
                'total_time' => $attempt->total_time,
                'completed' => (bool) $attempt->completed,
                'finished_at' => $attempt->finished_at?->toISOString(),
            ];
        })
            ->sort(function ($a, $b) {
                if ($a['completed'] !== $b['completed']) {
                    return $a['completed'] ? -1 : 1;
                }

                if ($a['completed'] && $b['completed']) {
                    if ($a['score'] !== $b['score']) {
                        return $b['score'] <=> $a['score'];
                    }

                    if (($a['total_time'] ?? PHP_INT_MAX) !== ($b['total_time'] ?? PHP_INT_MAX)) {
                        return ($a['total_time'] ?? PHP_INT_MAX) <=> ($b['total_time'] ?? PHP_INT_MAX);
                    }

                    return strcmp($a['finished_at'] ?? '', $b['finished_at'] ?? '');
                }

                return 0;
            })
            ->values();

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
            ],
            'results' => $results,
        ]);
    }

    private function calculatePenalty(int $currentScore, float $penaltyRate): int
    {
        return min($currentScore, (int) round($currentScore * $penaltyRate));
    }

    /**
     * Each participant gets their own randomized question order (stored on the attempt),
     * while the shared timing/position is always driven by the canonical quiz order.
     */
    private function resolveQuestionForAttempt(QuizAttempt $attempt, array $window, MidalarioTimeline $timeline): Question
    {
        $questionId = $attempt->question_order[$window['index']] ?? null;

        if ($questionId) {
            $question = $timeline->questions()->firstWhere('id', $questionId);

            if ($question) {
                return $question;
            }
        }

        return $window['question'];
    }

    private function ensureMidalario(Quiz $quiz): void
    {
        abort_unless($quiz->type === 'midalario', 404);
    }
}
