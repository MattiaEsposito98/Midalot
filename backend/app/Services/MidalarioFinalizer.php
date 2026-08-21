<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;

class MidalarioFinalizer
{
    private const TIMEOUT_PENALTY_RATE = 0.05;

    public function finalizeIfNeeded(Quiz $quiz): void
    {
        if ($quiz->midalario_status !== 'running') {
            return;
        }

        $timeline = new MidalarioTimeline($quiz);

        if (! $timeline->isFinished()) {
            return;
        }

        $questions = $timeline->questions();

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('completed', false)
            ->get();

        foreach ($attempts as $attempt) {
            foreach ($questions as $question) {
                $exists = QuizAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $currentScore = max(0, (int) QuizAnswer::where('attempt_id', $attempt->id)->sum('score'));

                QuizAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'answer_id' => null,
                    'time_taken' => (int) $question->time_limit_seconds * 1000,
                    'is_correct' => false,
                    'is_timeout' => true,
                    'is_wrong' => false,
                    'score' => -min($currentScore, (int) round($currentScore * self::TIMEOUT_PENALTY_RATE)),
                ]);
            }

            $totalScore = QuizAnswer::where('attempt_id', $attempt->id)->sum('score');
            $totalTime = QuizAnswer::where('attempt_id', $attempt->id)->sum('time_taken');

            $attempt->update([
                'score' => $totalScore,
                'total_time' => $totalTime,
                'completed' => true,
                'finished_at' => now(),
            ]);
        }

        $quiz->update(['midalario_status' => 'finished']);
    }
}
