<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use App\Models\MinigiocoRound;
use App\Models\MinigiocoRoundRisposta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MinigiocoPlayController extends Controller
{
    private const CORRECT_ANSWER_BASE_SCORE = 7000;

    private const MAX_SPEED_BONUS = 3000;

    private const WRONG_ANSWER_PENALTY_RATE = 0.10;

    private const TIMEOUT_PENALTY_RATE = 0.05;

    public function start(Request $request, Minigioco $minigioco)
    {
        $user = $request->user();

        $attempt = MinigiocoAttempt::where('minigioco_id', $minigioco->id)
            ->where('user_id', $user->id)
            ->first();

        if ($attempt && ! $attempt->completed) {
            $attempt->finalizeWithAccumulatedScore();
        }

        if ($attempt && (bool) $attempt->completed === true) {
            return response()->json([
                'message' => 'Il minigioco è stato interrotto o già completato: il punteggio accumulato è stato salvato e non è più riprendibile.',
                'attempt_id' => $attempt->id,
                'completed' => true,
                'score' => $attempt->score,
            ], 403);
        }

        if (! $minigioco->is_active) {
            return response()->json([
                'message' => 'Questo minigioco è scaduto e non è stato completato',
            ], 403);
        }

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        return response()->json([
            'message' => 'Minigioco avviato',
            'attempt_id' => $attempt->id,
            'completed' => false,
        ], 201);
    }

    public function submitAnswer(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:minigioco_attempts,id',
            'round_id' => 'required|exists:minigioco_round,id',
            'risposta' => 'nullable|string|max:100',
            'time_taken' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        $attempt = MinigiocoAttempt::findOrFail($request->attempt_id);

        if ((int) $attempt->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Tentativo non valido',
            ], 403);
        }

        if ((bool) $attempt->completed === true) {
            return response()->json([
                'message' => 'Minigioco già completato',
            ], 403);
        }

        $round = MinigiocoRound::findOrFail($request->round_id);

        if ((int) $round->minigioco_id !== (int) $attempt->minigioco_id) {
            return response()->json([
                'message' => 'Domanda non valida per questo minigioco',
            ], 422);
        }

        $row = MinigiocoRoundRisposta::firstOrCreate(
            ['attempt_id' => $attempt->id, 'round_id' => $round->id],
            ['risposta_utente' => null, 'tentativi_falliti' => 0, 'time_taken' => 0, 'score' => 0]
        );

        if ($row->is_correct || $row->is_timeout) {
            return response()->json([
                'message' => 'Hai già risolto questa domanda',
            ], 403);
        }

        $otherRoundsScore = (int) MinigiocoRoundRisposta::where('attempt_id', $attempt->id)
            ->where('round_id', '!=', $round->id)
            ->sum('score');

        $currentRunningTotal = max(0, $otherRoundsScore + $row->score);

        $maxTimeMs = (int) $round->time_limit_seconds * 1000;
        $timeTaken = min((int) $request->time_taken, $maxTimeMs);

        if ($request->risposta === null) {
            $penalty = $this->calculatePenalty($currentRunningTotal, self::TIMEOUT_PENALTY_RATE);

            $row->score -= $penalty;
            $row->is_timeout = true;
            $row->time_taken = $maxTimeMs;
            $row->save();

            return response()->json([
                'correct' => false,
                'timeout' => true,
                'tentativi_falliti' => $row->tentativi_falliti,
                'score' => $row->score,
            ]);
        }

        $rispostaNormalizzata = Str::upper(trim($request->risposta));

        if ($rispostaNormalizzata === $round->parola_originale) {
            $speedBonus = (int) round((($maxTimeMs - $timeTaken) / $maxTimeMs) * self::MAX_SPEED_BONUS);

            $row->score += self::CORRECT_ANSWER_BASE_SCORE + $speedBonus;
            $row->is_correct = true;
            $row->risposta_utente = $rispostaNormalizzata;
            $row->time_taken = $timeTaken;
            $row->save();

            return response()->json([
                'correct' => true,
                'timeout' => false,
                'tentativi_falliti' => $row->tentativi_falliti,
                'score' => $row->score,
            ]);
        }

        $penalty = $this->calculatePenalty($currentRunningTotal, self::WRONG_ANSWER_PENALTY_RATE);

        $row->score -= $penalty;
        $row->tentativi_falliti += 1;
        $row->risposta_utente = $rispostaNormalizzata;
        $row->save();

        return response()->json([
            'correct' => false,
            'timeout' => false,
            'tentativi_falliti' => $row->tentativi_falliti,
            'score' => $row->score,
        ]);
    }

    public function finishMinigioco(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:minigioco_attempts,id',
        ]);

        $user = $request->user();
        $attempt = MinigiocoAttempt::findOrFail($request->attempt_id);

        if ((int) $attempt->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Tentativo non valido',
            ], 403);
        }

        if ($attempt->completed) {
            return response()->json([
                'message' => 'Minigioco già completato',
            ], 403);
        }

        $totalScore = MinigiocoRoundRisposta::where('attempt_id', $attempt->id)->sum('score');
        $totalTime = MinigiocoRoundRisposta::where('attempt_id', $attempt->id)->sum('time_taken');

        $attempt->update([
            'score' => $totalScore,
            'total_time' => $totalTime,
            'completed' => true,
            'finished_at' => now(),
        ]);

        return response()->json([
            'score' => $totalScore,
        ]);
    }

    private function calculatePenalty(int $currentScore, float $penaltyRate): int
    {
        return min($currentScore, (int) round($currentScore * $penaltyRate));
    }
}
