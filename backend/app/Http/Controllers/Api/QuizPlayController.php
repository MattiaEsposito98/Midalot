<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizPlayController extends Controller
{
    public function start(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        if (!$user->quizzes()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'Quiz non assegnato'
            ], 403);
        }

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        if ($attempt && (bool) $attempt->completed === true) {
            return response()->json([
                'message' => 'Hai già completato questo quiz',
                'attempt_id' => $attempt->id,
                'completed' => true,
            ], 403);
        }

        if (!$quiz->is_active) {
            return response()->json([
                'message' => 'Questo quiz è scaduto e non è stato completato'
            ], 403);
        }

        if ($attempt) {
            return response()->json([
                'message' => 'Quiz già iniziato, riprendo il tentativo',
                'attempt_id' => $attempt->id,
                'completed' => false,
            ], 200);
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        return response()->json([
            'message' => 'Quiz avviato',
            'attempt_id' => $attempt->id,
            'completed' => false,
        ], 201);
    }

    public function submitAnswer(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'nullable|exists:answers,id',
            'time_taken' => 'required|integer|min:0'
        ]);

        $user = $request->user();

        $attempt = QuizAttempt::findOrFail($request->attempt_id);

        if ((int) $attempt->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Tentativo non valido'
            ], 403);
        }

        if ((bool) $attempt->completed === true) {
            return response()->json([
                'message' => 'Quiz già completato'
            ], 403);
        }

        $question = Question::findOrFail($request->question_id);

        if ((int) $question->quiz_id !== (int) $attempt->quiz_id) {
            return response()->json([
                'message' => 'Domanda non valida per questo quiz'
            ], 422);
        }

        $alreadyAnswered = QuizAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json([
                'message' => 'Hai già risposto a questa domanda'
            ], 403);
        }

        $maxTimeMs = (int) $question->time_limit_seconds * 1000;
        $timeTaken = min((int) $request->time_taken, $maxTimeMs);

        $answerId = null;
        $isCorrect = false;
        $isTimeout = false;
        $isWrong = false;
        $score = 0;

        if ($request->answer_id === null) {
            $isTimeout = true;
        } else {
            $answer = Answer::where('id', $request->answer_id)
                ->where('question_id', $question->id)
                ->first();

            if (!$answer) {
                return response()->json([
                    'message' => 'Risposta non valida'
                ], 422);
            }

            $answerId = $answer->id;
            $isCorrect = (bool) $answer->is_correct;

            if ($isCorrect) {
                $speedScore = (int) round((($maxTimeMs - $timeTaken) / $maxTimeMs) * 100000);
                $score = 100000 + $speedScore;
            } else {
                $isWrong = true;
            }
        }

        QuizAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_id' => $answerId,
            'time_taken' => $timeTaken,
            'is_correct' => $isCorrect,
            'is_timeout' => $isTimeout,
            'is_wrong' => $isWrong,
            'score' => $score,
        ]);

        return response()->json([
            'correct' => $isCorrect,
            'wrong' => $isWrong,
            'timeout' => $isTimeout,
            'score' => $score,
        ]);
    }

    public function finishQuiz(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id'
        ]);

        $user = $request->user();
        $attempt = QuizAttempt::findOrFail($request->attempt_id);

        if ((int)$attempt->user_id !== (int)$user->id) {
            return response()->json([
                'message' => 'Tentativo non valido'
            ], 403);
        }

        if ($attempt->completed) {
            return response()->json([
                'message' => 'Quiz già completato'
            ], 403);
        }

        $totalScore = QuizAnswer::where('attempt_id', $attempt->id)->sum('score');
        $totalTime = QuizAnswer::where('attempt_id', $attempt->id)->sum('time_taken');

        $attempt->update([
            'score' => $totalScore,
            'total_time' => $totalTime,
            'completed' => true,
            'finished_at' => now()
        ]);

        return response()->json([
            'score' => $totalScore
        ]);
    }
}
