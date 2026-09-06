<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use App\Models\MinigiocoRound;
use App\Models\MinigiocoRoundRisposta;
use App\Services\AnswerTimer;
use App\Services\SaltoTemporaleItemOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MinigiocoPlayController extends Controller
{
    // Quota di punteggio per round corretto: 70% fisso, 30% bonus velocità.
    private const BASE_SHARE = 0.7;

    private const SPEED_BONUS_SHARE = 0.3;

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

        $minigioco = $attempt->minigioco;

        return match ($minigioco->tipo) {
            'salto_temporale' => $this->submitSaltoTemporale($request, $attempt, $round, $minigioco),
            'trova_intruso' => $this->submitTrovaIntruso($request, $attempt, $round, $minigioco),
            default => $this->submitTastieraRotta($request, $attempt, $round, $minigioco),
        };
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

        $totalScore = (int) MinigiocoRoundRisposta::where('attempt_id', $attempt->id)->sum('score');
        $totalScore = min($totalScore, $attempt->minigioco->max_score * 100);
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

    /**
     * Tastiera Rotta: gameplay invariato (retry finché non scade il timer,
     * penalità percentuale su risposta sbagliata/timeout), solo con la
     * quota di punteggio per round ora calcolata dinamicamente sul tetto
     * massimo configurabile del minigioco invece di costanti fisse.
     */
    /**
     * Istante in cui il round corrente e' di fatto iniziato per il giocatore:
     * la fine del round precedente, o l'inizio del tentativo se e' il primo.
     * Serve ad AnswerTimer per verificare il tempo dichiarato dal client.
     * Su Tastiera Rotta i tentativi multipli restano dentro lo stesso round,
     * quindi il cronometro continua a correre tra un tentativo e l'altro.
     */
    private function roundStartedAt(MinigiocoAttempt $attempt, MinigiocoRound $round): ?Carbon
    {
        $previousRoundAt = MinigiocoRoundRisposta::where('attempt_id', $attempt->id)
            ->where('round_id', '!=', $round->id)
            ->max('updated_at');

        return $previousRoundAt ? Carbon::parse($previousRoundAt) : $attempt->started_at;
    }

    private function submitTastieraRotta(Request $request, MinigiocoAttempt $attempt, MinigiocoRound $round, Minigioco $minigioco)
    {
        $request->validate(['risposta' => 'nullable|string|max:100']);

        ['base' => $base, 'maxSpeedBonus' => $maxSpeedBonus] = $this->perRoundBudget($minigioco);

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
        $timeTaken = AnswerTimer::resolve((int) $request->time_taken, $this->roundStartedAt($attempt, $round), $maxTimeMs);

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
            $speedBonus = (int) round((($maxTimeMs - $timeTaken) / $maxTimeMs) * $maxSpeedBonus);

            $row->score += (int) round($base) + $speedBonus;
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

    /**
     * Salto Temporale: un solo tentativo (nessun retry). Corretto se l'ordine
     * proposto dal giocatore coincide con l'ordine degli item per 'ordine' crescente.
     */
    private function submitSaltoTemporale(Request $request, MinigiocoAttempt $attempt, MinigiocoRound $round, Minigioco $minigioco)
    {
        $request->validate([
            'risposta' => 'nullable|array',
            'risposta.*' => 'integer',
        ]);

        ['base' => $base, 'maxSpeedBonus' => $maxSpeedBonus] = $this->perRoundBudget($minigioco);

        $row = MinigiocoRoundRisposta::firstOrCreate(
            ['attempt_id' => $attempt->id, 'round_id' => $round->id],
            ['risposta_utente' => null, 'tentativi_falliti' => 0, 'time_taken' => 0, 'score' => 0]
        );

        if ($row->is_correct || $row->is_timeout || $row->tentativi_falliti > 0) {
            return response()->json([
                'message' => 'Hai già risolto questa domanda',
            ], 403);
        }

        $maxTimeMs = (int) $round->time_limit_seconds * 1000;
        $timeTaken = AnswerTimer::resolve((int) $request->time_taken, $this->roundStartedAt($attempt, $round), $maxTimeMs);

        if ($request->risposta === null) {
            $row->is_timeout = true;
            $row->time_taken = $maxTimeMs;
            $row->score = 0;
            $row->save();

            return response()->json(['correct' => false, 'timeout' => true, 'score' => 0]);
        }

        // Il client ragiona sugli id permutati: vanno ritradotti prima del
        // confronto. Si salvano gli id reali, cosi' il riepilogo resta coerente.
        $displayToReal = SaltoTemporaleItemOrder::displayToReal($round, (int) $attempt->user_id);
        $correctOrder = $round->items->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedOrder = array_map(
            fn ($id) => $displayToReal[(int) $id] ?? 0,
            $request->risposta
        );

        $isCorrect = $submittedOrder === $correctOrder;

        $row->risposta_utente = json_encode($submittedOrder);
        $row->time_taken = $timeTaken;

        if ($isCorrect) {
            $speedBonus = (int) round((($maxTimeMs - $timeTaken) / $maxTimeMs) * $maxSpeedBonus);
            $row->score = (int) round($base) + $speedBonus;
            $row->is_correct = true;
        } else {
            $row->score = 0;
            $row->tentativi_falliti = 1;
        }

        $row->save();

        return response()->json(['correct' => $isCorrect, 'timeout' => false, 'score' => $row->score]);
    }

    /**
     * Trova l'Intruso: un solo tentativo (nessun retry). Corretto se l'item
     * scelto dal giocatore ha is_intruso=true.
     */
    private function submitTrovaIntruso(Request $request, MinigiocoAttempt $attempt, MinigiocoRound $round, Minigioco $minigioco)
    {
        $request->validate(['risposta' => 'nullable|integer']);

        ['base' => $base, 'maxSpeedBonus' => $maxSpeedBonus] = $this->perRoundBudget($minigioco);

        $row = MinigiocoRoundRisposta::firstOrCreate(
            ['attempt_id' => $attempt->id, 'round_id' => $round->id],
            ['risposta_utente' => null, 'tentativi_falliti' => 0, 'time_taken' => 0, 'score' => 0]
        );

        if ($row->is_correct || $row->is_timeout || $row->tentativi_falliti > 0) {
            return response()->json([
                'message' => 'Hai già risolto questa domanda',
            ], 403);
        }

        $maxTimeMs = (int) $round->time_limit_seconds * 1000;
        $timeTaken = AnswerTimer::resolve((int) $request->time_taken, $this->roundStartedAt($attempt, $round), $maxTimeMs);

        if ($request->risposta === null) {
            $row->is_timeout = true;
            $row->time_taken = $maxTimeMs;
            $row->score = 0;
            $row->save();

            return response()->json(['correct' => false, 'timeout' => true, 'score' => 0]);
        }

        $selectedItem = $round->items()->find($request->risposta);
        $isCorrect = (bool) $selectedItem?->is_intruso;

        $row->risposta_utente = (string) $request->risposta;
        $row->time_taken = $timeTaken;

        if ($isCorrect) {
            $speedBonus = (int) round((($maxTimeMs - $timeTaken) / $maxTimeMs) * $maxSpeedBonus);
            $row->score = (int) round($base) + $speedBonus;
            $row->is_correct = true;
        } else {
            $row->score = 0;
            $row->tentativi_falliti = 1;
        }

        $row->save();

        return response()->json(['correct' => $isCorrect, 'timeout' => false, 'score' => $row->score]);
    }

    /**
     * Distribuisce il tetto massimo di punti del minigioco sui suoi round:
     * ogni round corretto vale al massimo max_score/numero_round, suddiviso
     * in una quota fissa (70%) e un bonus velocità (30%).
     *
     * `max_score` è il valore MOSTRATO all'utente (es. 30): come ogni altro
     * punteggio del progetto, il valore grezzo salvato nel DB è x100
     * (frontend/src/utils/quizScore.js divide per 100 in visualizzazione),
     * quindi qui il budget viene calcolato su `max_score * 100`.
     */
    private function perRoundBudget(Minigioco $minigioco): array
    {
        $roundsCount = max(1, MinigiocoRound::where('minigioco_id', $minigioco->id)->count());
        $perRoundMax = ($minigioco->max_score * 100) / $roundsCount;

        return [
            'base' => $perRoundMax * self::BASE_SHARE,
            'maxSpeedBonus' => $perRoundMax * self::SPEED_BONUS_SHARE,
        ];
    }

    private function calculatePenalty(int $currentScore, float $penaltyRate): int
    {
        return min($currentScore, (int) round($currentScore * $penaltyRate));
    }
}
