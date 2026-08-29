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
            $q->inRandomOrder()
                ->select('id', 'minigioco_id', 'parola_originale', 'shift', 'time_limit_seconds');
        }]);

        return response()->json([
            'minigioco' => [
                'id' => $minigioco->id,
                'title' => $minigioco->title,
                'description' => $minigioco->description,
                'total_rounds' => $minigioco->rounds->count(),
                'total_time_seconds' => $minigioco->rounds->sum('time_limit_seconds'),
                'leaderboard_visible' => (bool) $minigioco->leaderboard_visible,
                'rounds' => $minigioco->rounds->map(function ($round) {
                    return [
                        'id' => $round->id,
                        'parola_cifrata' => $round->parola_cifrata,
                        'time_limit_seconds' => $round->time_limit_seconds,
                    ];
                }),
            ],
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

        $minigioco->load(['rounds' => function ($q) {
            $q->inRandomOrder()
                ->select('id', 'minigioco_id', 'parola_originale', 'shift', 'time_limit_seconds');
        }]);

        $givenRisposte = MinigiocoRoundRisposta::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('round_id');

        $rounds = $minigioco->rounds->map(function ($round) use ($givenRisposte) {
            $given = $givenRisposte->get($round->id);

            return [
                'id' => $round->id,
                'parola_cifrata' => $round->parola_cifrata,
                'time_limit_seconds' => $round->time_limit_seconds,
                'parola_corretta' => $round->parola_originale,
                'risposta_utente' => $given?->risposta_utente,
                'tentativi_falliti' => $given?->tentativi_falliti ?? 0,
                'is_correct' => (bool) ($given?->is_correct ?? false),
                'is_timeout' => (bool) ($given?->is_timeout ?? false),
                'time_taken' => $given?->time_taken,
                'score' => $given?->score ?? 0,
            ];
        });

        return response()->json([
            'minigioco' => [
                'id' => $minigioco->id,
                'title' => $minigioco->title,
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
