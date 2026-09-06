<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class UserQuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $assignedQuizIds = $user->quizzes()->pluck('quizzes.id');

        $quizzes = Quiz::where('type', 'assigned')
            ->where(function ($q) use ($assignedQuizIds) {
                $q->where('restrict_to_specific_users', false)
                    ->orWhereIn('id', $assignedQuizIds);
            })
            ->withCount('questions')
            ->withAvg('questions', 'time_limit_seconds')
            ->withSum('questions', 'time_limit_seconds')
            ->orderByDesc('is_active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($quiz) use ($user) {
                $attempt = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->first();

                $completed = (bool) ($attempt?->completed ?? false);
                $isActive = (bool) $quiz->is_active;

                if ($completed) {
                    $status = 'completed';
                } elseif (!$isActive) {
                    $status = 'expired';
                } elseif ($attempt) {
                    $status = 'in_progress';
                } else {
                    $status = 'available';
                }

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'image' => $quiz->image_url,
                    'is_active' => $isActive,
                    'status' => $status,
                    'completed' => $completed,
                    'expired' => !$isActive && !$completed,
                    'questions_count' => $quiz->questions_count,
                    'avg_time' => $quiz->questions_avg_time_limit_seconds,
                    'total_time' => $quiz->questions_sum_time_limit_seconds,
                    'score' => $attempt?->score,
                    'attempt_id' => $attempt?->id,
                    'finished_at' => $attempt?->finished_at,
                    'leaderboard_visible' => (bool) $quiz->leaderboard_visible,
                ];
            });

        return response()->json([
            'quizzes' => $quizzes
        ]);
    }

    /**
     * Questo controller serve solo i Quiz One Shot. Senza questo controllo un
     * utente potrebbe passare l'id di un quiz Midalario e leggerne domande e
     * risposte prima della diretta (restrict_to_specific_users e' false di
     * default, quindi la guardia sull'assegnazione non scatterebbe).
     */
    private function ensureOneShot(Quiz $quiz): void
    {
        abort_unless($quiz->type === 'assigned', 404);
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->ensureOneShot($quiz);

        $user = $request->user();

        if ($quiz->restrict_to_specific_users && !$user->quizzes()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'Quiz non assegnato'
            ], 403);
        }

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        if ($attempt && !$attempt->completed) {
            $wasInterrupted = true;
            $attempt->finalizeWithAccumulatedScore();
        } else {
            $wasInterrupted = false;
        }

        if ($attempt && $attempt->completed) {
            return response()->json([
                'message' => $wasInterrupted
                    ? 'Il quiz era stato interrotto: il punteggio accumulato fino a quel momento è stato salvato e il quiz non è più riprendibile.'
                    : 'Hai già completato questo quiz',
                'already_completed' => true,
                'interrupted' => $wasInterrupted,
                'score' => $attempt->score,
                'finished_at' => $attempt->finished_at,
            ], 403);
        }

        if (!$quiz->is_active) {
            return response()->json([
                'message' => 'Questo quiz è scaduto e non è stato completato'
            ], 403);
        }

        $quiz->load([
            'questions' => function ($q) {
                $q->inRandomOrder()
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
            'questions.answers:id,question_id,answer_text'
        ]);

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'total_questions' => $quiz->questions->count(),
                'total_time_seconds' => $quiz->questions->sum('time_limit_seconds'),
                'leaderboard_visible' => (bool) $quiz->leaderboard_visible,
                'questions' => $quiz->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'image' => $question->image_path ? asset('storage/' . $question->image_path) : null,
                        'audio' => $question->resolvedAudioUrl(),
                        'audio_start_seconds' => $question->audio_start_seconds,
                        'audio_end_seconds' => $question->audio_end_seconds,
                        'video' => $question->video_path ? asset('storage/' . $question->video_path) : null,
                        'time_limit_seconds' => $question->time_limit_seconds,
                        'answers' => $question->answers->map(function ($answer) {
                            return [
                                'id' => $answer->id,
                                'answer_text' => $answer->answer_text,
                            ];
                        }),
                    ];
                }),
            ]
        ]);
    }

    public function review(Request $request, Quiz $quiz)
    {
        $this->ensureOneShot($quiz);

        $user = $request->user();

        if ($quiz->restrict_to_specific_users && !$user->quizzes()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'Quiz non assegnato'
            ], 403);
        }

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->first();

        if (!$attempt) {
            return response()->json([
                'message' => 'Devi completare il quiz prima di poter vedere il riepilogo'
            ], 403);
        }

        $quiz->load([
            'questions' => function ($q) {
                $q->inRandomOrder()
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

        $questions = $quiz->questions->map(function ($question) use ($givenAnswers) {
            $given = $givenAnswers->get($question->id);
            $correctAnswer = $question->answers->firstWhere('is_correct', true);
            $givenAnswer = $given?->answer_id
                ? $question->answers->firstWhere('id', $given->answer_id)
                : null;

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'image' => $question->image_path ? asset('storage/' . $question->image_path) : null,
                'audio' => $question->resolvedAudioUrl(),
                'audio_start_seconds' => $question->audio_start_seconds,
                'audio_end_seconds' => $question->audio_end_seconds,
                'video' => $question->video_path ? asset('storage/' . $question->video_path) : null,
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
            'leaderboard_visible' => (bool) $quiz->leaderboard_visible,
            'questions' => $questions,
        ]);
    }

    public function leaderboard(Request $request, Quiz $quiz)
    {
        $this->ensureOneShot($quiz);

        $user = $request->user();

        if ($quiz->restrict_to_specific_users && !$user->quizzes()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'Quiz non assegnato'
            ], 403);
        }

        if (!$quiz->leaderboard_visible) {
            return response()->json([
                'message' => 'Classifica non disponibile'
            ], 403);
        }

        $totalQuestions = $quiz->questions()->count();

        $attempts = QuizAttempt::with(['user.latestMonthlyBadge', 'answers'])
            ->where('quiz_id', $quiz->id)
            ->orderByDesc('completed')
            ->orderByDesc('score')
            ->orderByRaw('total_time IS NULL, total_time ASC')
            ->orderBy('finished_at')
            ->orderBy('id')
            ->limit(50)
            ->get();

        $results = $attempts->map(function ($attempt) use ($totalQuestions) {
            $correct = $attempt->answers?->where('is_correct', true)->count() ?? 0;

            return [
                'user' => [
                    'nickname' => $attempt->user->nickname ?? 'Utente',
                    'badge' => $attempt->user->latestMonthlyBadge?->label,
                ],
                'score' => $attempt->score ?? 0,
                'correct_answers' => $correct,
                'total_questions' => $totalQuestions,
                'total_time' => $attempt->total_time,
                'completed' => (bool) $attempt->completed,
                'finished_at' => $attempt->finished_at?->toISOString(),
            ];
        })->values();

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
            ],
            'results' => $results
        ]);
    }
}
