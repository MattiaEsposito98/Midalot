<?php

namespace App\Services;

use App\Models\Challenge;
use Illuminate\Support\Collection;

/**
 * Timeline condivisa della sfida live: come per il Midalario, e' il server a
 * decidere quale domanda e' attiva e fino a quando, cosi' i due giocatori
 * vedono la stessa domanda nello stesso momento.
 */
class ChallengeLiveTimeline
{
    private ?Collection $questions = null;

    public function __construct(private Challenge $challenge)
    {
    }

    public function questions(): Collection
    {
        if ($this->questions === null) {
            $this->questions = $this->challenge->questions()->with('answers')->get();
        }

        return $this->questions;
    }

    /**
     * Finestre di tutte le domande: index, question, starts_at, ends_at.
     *
     * @return array<int, array{index: int, question: \App\Models\Question, starts_at: \Illuminate\Support\Carbon, ends_at: \Illuminate\Support\Carbon}>
     */
    public function windows(): array
    {
        $cursor = $this->challenge->live_started_at->copy();
        $windows = [];

        foreach ($this->questions() as $index => $question) {
            $endsAt = $cursor->copy()->addSeconds((int) $question->time_limit_seconds);
            $windows[] = [
                'index' => $index,
                'question' => $question,
                'starts_at' => $cursor,
                'ends_at' => $endsAt,
            ];
            $cursor = $endsAt;
        }

        return $windows;
    }

    public function hasStarted(): bool
    {
        return $this->challenge->live_started_at !== null && now()->gte($this->challenge->live_started_at);
    }

    public function currentWindow(): ?array
    {
        if (! $this->hasStarted()) {
            return null;
        }

        $now = now();

        foreach ($this->windows() as $window) {
            if ($now->lt($window['ends_at'])) {
                return $window;
            }
        }

        return null;
    }

    public function isFinished(): bool
    {
        return $this->hasStarted() && $this->currentWindow() === null;
    }
}
