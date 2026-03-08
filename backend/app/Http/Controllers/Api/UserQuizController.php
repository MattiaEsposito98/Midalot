<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class UserQuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $quizzes = $user->quizzes()
            ->withCount('questions')
            ->withAvg('questions', 'time_limit_seconds')
            ->withSum('questions', 'time_limit_seconds')
            ->orderByDesc('quizzes.is_active')
            ->orderBy('quizzes.created_at', 'desc')
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
                ];
            });

        return response()->json([
            'quizzes' => $quizzes
        ]);
    }

    public function show(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        if (!$user->quizzes()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'Quiz non assegnato'
            ], 403);
        }

        $completedAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->get()
            ->first(function ($attempt) {
                return (bool) $attempt->completed === true;
            });

        if ($completedAttempt) {
            return response()->json([
                'message' => 'Hai già completato questo quiz',
                'already_completed' => true,
                'score' => $completedAttempt->score,
                'finished_at' => $completedAttempt->finished_at,
            ], 403);
        }

        if (!$quiz->is_active) {
            return response()->json([
                'message' => 'Questo quiz è scaduto e non è stato completato'
            ], 403);
        }

        $quiz->load([
            'questions' => function ($q) {
                $q->orderBy('order')
                    ->select(
                        'id',
                        'quiz_id',
                        'question_text',
                        'image_path',
                        'audio_path',
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
                'questions' => $quiz->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'image' => $question->image_path ? asset('storage/' . $question->image_path) : null,
                        'audio' => $question->audio_path ? asset('storage/' . $question->audio_path) : null,
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
}
