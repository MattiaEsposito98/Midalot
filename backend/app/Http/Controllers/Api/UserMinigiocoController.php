<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use App\Models\MinigiocoRoundRisposta;
use Illuminate\Http\Request;

class UserMinigiocoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $minigiochi = Minigioco::withCount('rounds')
            ->withSum('rounds', 'time_limit_seconds')
            ->orderByDesc('is_active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($minigioco) use ($user) {
                $attempt = MinigiocoAttempt::where('minigioco_id', $minigioco->id)
                    ->where('user_id', $user->id)
                    ->first();

                $completed = (bool) ($attempt?->completed ?? false);
                $isActive = (bool) $minigioco->is_active;

                if ($completed) {
                    $status = 'completed';
                } elseif (! $isActive) {
                    $status = 'expired';
                } elseif ($attempt) {
                    $status = 'in_progress';
                } else {
                    $status = 'available';
                }

                return [
                    'id' => $minigioco->id,
                    'title' => $minigioco->title,
                    'description' => $minigioco->description,
                    'image' => $minigioco->image_url,
                    'tipo' => $minigioco->tipo,
                    'is_active' => $isActive,
                    'status' => $status,
                    'completed' => $completed,
                    'expired' => ! $isActive && ! $completed,
                    'rounds_count' => $minigioco->rounds_count,
                    'total_time' => $minigioco->rounds_sum_time_limit_seconds,
                    'score' => $attempt?->score,
                    'attempt_id' => $attempt?->id,
                    'finished_at' => $attempt?->finished_at,
                    'leaderboard_visible' => (bool) $minigioco->leaderboard_visible,
                ];
            });

        return response()->json([
            'minigiochi' => $minigiochi,
        ]);
    }

    public function show(Request $request, Minigioco $minigioco)
    {
        $user = $request->user();

        $attempt = MinigiocoAttempt::where('minigioco_id', $minigioco->id)
            ->where('user_id', $user->id)
            ->first();

        if ($attempt && ! $attempt->completed) {
            $wasInterrupted = true;
            $attempt->finalizeWithAccumulatedScore();
        } else {
            $wasInterrupted = false;
        }

        if ($attempt && $attempt->completed) {
            return response()->json([
                'message' => $wasInterrupted
                    ? 'Il minigioco era stato interrotto: il punteggio accumulato è stato salvato e non è più riprendibile.'
                    : 'Hai già completato questo minigioco',
                'already_completed' => true,
                'interrupted' => $wasInterrupted,
                'score' => $attempt->score,
                'finished_at' => $attempt->finished_at,
            ], 403);
        }

        if (! $minigioco->is_active) {
            return response()->json([
                'message' => 'Questo minigioco è scaduto e non è stato completato',
            ], 403);
        }

        $minigioco->load(['rounds' => function ($q) {
            $q->inRandomOrder();
        }, 'rounds.items']);

        return response()->json([
            'minigioco' => [
                'id' => $minigioco->id,
                'title' => $minigioco->title,
                'description' => $minigioco->description,
                'tipo' => $minigioco->tipo,
                'max_score' => $minigioco->max_score,
                'total_rounds' => $minigioco->rounds->count(),
                'total_time_seconds' => $minigioco->rounds->sum('time_limit_seconds'),
                'leaderboard_visible' => (bool) $minigioco->leaderboard_visible,
                'rounds' => $minigioco->rounds->map(fn ($round) => $this->roundPlayPayload($round, $minigioco->tipo)),
            ],
        ]);
    }

    /**
     * Payload di un round per il gioco (dati mai rivelano la risposta corretta):
     * Tastiera Rotta espone la parola cifrata; Salto Temporale/Trova l'Intruso
     * espongono i 4 elementi mescolati casualmente.
     */
    private function roundPlayPayload($round, string $tipo): array
    {
        $base = [
            'id' => $round->id,
            'time_limit_seconds' => $round->time_limit_seconds,
        ];

        if ($tipo === 'tastiera_rotta') {
            return [...$base, 'parola_cifrata' => $round->parola_cifrata];
        }

        return [...$base, 'items' => $this->itemsPayload($round)->shuffle()->values()];
    }

    private function itemsPayload($round)
    {
        return $round->items->map(fn ($item) => [
            'id' => $item->id,
            'label' => $item->label,
            'image' => $item->image_url,
        ]);
    }

    public function review(Request $request, Minigioco $minigioco)
    {
        $user = $request->user();

        $attempt = MinigiocoAttempt::where('minigioco_id', $minigioco->id)
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->first();

        if (! $attempt) {
            return response()->json([
                'message' => 'Devi completare il minigioco prima di poter vedere il riepilogo',
            ], 403);
        }

        $minigioco->load(['rounds', 'rounds.items']);

        $givenRisposte = MinigiocoRoundRisposta::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('round_id');

        $rounds = $minigioco->rounds->map(function ($round) use ($givenRisposte, $minigioco) {
            $given = $givenRisposte->get($round->id);

            $base = [
                'id' => $round->id,
                'time_limit_seconds' => $round->time_limit_seconds,
                'tentativi_falliti' => $given?->tentativi_falliti ?? 0,
                'is_correct' => (bool) ($given?->is_correct ?? false),
                'is_timeout' => (bool) ($given?->is_timeout ?? false),
                'time_taken' => $given?->time_taken,
                'score' => $given?->score ?? 0,
            ];

            if ($minigioco->tipo === 'salto_temporale') {
                return [
                    ...$base,
                    'items_corretti' => $this->itemsPayload($round)->values(),
                    'ordine_utente' => $given?->risposta_utente ? json_decode($given->risposta_utente, true) : null,
                ];
            }

            if ($minigioco->tipo === 'trova_intruso') {
                $intrusoItem = $round->items->firstWhere('is_intruso', true);
                $sceltoId = $given?->risposta_utente !== null ? (int) $given->risposta_utente : null;

                return [
                    ...$base,
                    'items' => $this->itemsPayload($round)->values(),
                    'intruso_id' => $intrusoItem?->id,
                    'scelto_id' => $sceltoId,
                ];
            }

            return [
                ...$base,
                'parola_cifrata' => $round->parola_cifrata,
                'parola_corretta' => $round->parola_originale,
                'risposta_utente' => $given?->risposta_utente,
            ];
        });

        return response()->json([
            'minigioco' => [
                'id' => $minigioco->id,
                'title' => $minigioco->title,
                'tipo' => $minigioco->tipo,
            ],
            'score' => $attempt->score,
            'total_time' => $attempt->total_time,
            'finished_at' => $attempt->finished_at,
            'leaderboard_visible' => (bool) $minigioco->leaderboard_visible,
            'rounds' => $rounds,
        ]);
    }

    public function leaderboard(Request $request, Minigioco $minigioco)
    {
        if (! $minigioco->leaderboard_visible) {
            return response()->json([
                'message' => 'Classifica non disponibile',
            ], 403);
        }

        $totalRounds = $minigioco->rounds()->count();

        $attempts = MinigiocoAttempt::with(['user', 'risposte'])
            ->where('minigioco_id', $minigioco->id)
            ->get();

        $results = $attempts->map(function ($attempt) use ($totalRounds) {
            $correct = $attempt->risposte?->where('is_correct', true)->count() ?? 0;

            return [
                'user' => [
                    'nickname' => $attempt->user->nickname ?? 'Utente',
                ],
                'score' => $attempt->score ?? 0,
                'correct_answers' => $correct,
                'total_questions' => $totalRounds,
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

                    return ($a['total_time'] ?? PHP_INT_MAX) <=> ($b['total_time'] ?? PHP_INT_MAX);
                }

                return 0;
            })
            ->values();

        return response()->json([
            'minigioco' => [
                'id' => $minigioco->id,
                'title' => $minigioco->title,
            ],
            'results' => $results,
        ]);
    }
}
