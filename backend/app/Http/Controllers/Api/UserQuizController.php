<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserQuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $quizzes = $user->quizzes()
            ->where('is_active', true)
            ->withCount('questions')
            ->select('quizzes.id', 'title', 'description')
            ->orderBy('quizzes.created_at', 'desc')
            ->get();

        return response()->json([
            'quizzes' => $quizzes
        ]);
    }

    public function show(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        // sicurezza: controlla che il quiz sia assegnato all'utente
        if (!$user->quizzes()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'Quiz non assegnato'
            ], 403);
        }

        if (!$quiz->is_active) {
            return response()->json([
                'message' => 'Quiz non attivo'
            ], 403);
        }

        $quiz->load([
            'questions.answers'
        ]);

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
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
