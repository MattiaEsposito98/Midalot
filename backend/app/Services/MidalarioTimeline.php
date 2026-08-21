<?php

namespace App\Services;

use App\Models\Quiz;
use Illuminate\Support\Collection;

class MidalarioTimeline
{
    private ?Collection $questions = null;

    public function __construct(private Quiz $quiz)
    {
    }

    public function questions(): Collection
    {
        if ($this->questions === null) {
            $this->questions = $this->quiz->questions()->orderBy('order')->get();
        }

        return $this->questions;
    }

    public function totalSeconds(): int
    {
        return (int) $this->questions()->sum('time_limit_seconds');
    }

    /**
     * Returns the currently active question window (index, question, starts_at, ends_at),
     * or null if the quiz hasn't started yet or every question's time has already elapsed.
     */
    public function currentWindow(): ?array
    {
        if (! $this->quiz->midalario_started_at) {
            return null;
        }

        $cursor = $this->quiz->midalario_started_at->copy();
        $now = now();

        foreach ($this->questions() as $index => $question) {
            $endsAt = $cursor->copy()->addSeconds((int) $question->time_limit_seconds);

            if ($now->lt($endsAt)) {
                return [
                    'index' => $index,
                    'question' => $question,
                    'starts_at' => $cursor,
                    'ends_at' => $endsAt,
                ];
            }

            $cursor = $endsAt;
        }

        return null;
    }

    public function isFinished(): bool
    {
        return $this->quiz->midalario_started_at !== null && $this->currentWindow() === null;
    }
}
