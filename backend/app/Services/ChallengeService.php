<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChallengeGuestAttempt;
use App\Models\Challenge;
use App\Models\ChallengeAnswer;
use App\Models\ChallengeAttempt;
use App\Models\ChallengeParticipant;
use App\Models\Question;
use App\Models\XpEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Logica delle sfide (da 2 a 5 giocatori): pesca delle domande, punteggio,
 * classifica, chiusura della sfida e assegnazione degli XP.
 */
class ChallengeService
{
    // Stessi valori di Quiz e Training: 7000 base + fino a 3000 di velocita'.
    public const CORRECT_ANSWER_BASE_SCORE = 7000;

    public const MAX_SPEED_BONUS = 3000;

    private const WRONG_ANSWER_PENALTY_RATE = 0.10;

    private const TIMEOUT_PENALTY_RATE = 0.05;

    public const MAX_SCORE_PER_QUESTION = self::CORRECT_ANSWER_BASE_SCORE + self::MAX_SPEED_BONUS;

    public const ASYNC_HOURS = 72;

    public const LIVE_INVITE_MINUTES = 10;

    public const LIVE_COUNTDOWN_SECONDS = 5;

    /**
     * Domande dei Training attivi che hanno una risposta corretta. Una
     * categoria in $categoryIds include tutto il suo contenuto, una
     * sottocategoria in $subcategoryIds tutti i suoi Training, un Training in
     * $quizIds solo se stesso. Tutti vuoti = tutte le categorie.
     */
    public function poolQuery(?array $categoryIds, ?array $subcategoryIds = null, ?array $quizIds = null)
    {
        return Question::query()
            ->whereHas('answers', fn ($q) => $q->where('is_correct', true))
            ->whereHas('quiz', function ($quiz) use ($categoryIds, $subcategoryIds, $quizIds) {
                $quiz->where('type', 'training')
                    ->where('is_active', true)
                    ->whereHas('trainingCategory', fn ($c) => $c->where('is_active', true));

                if ($categoryIds || $subcategoryIds || $quizIds) {
                    $quiz->where(function ($selection) use ($categoryIds, $subcategoryIds, $quizIds) {
                        if ($categoryIds) {
                            $selection->orWhereIn('training_category_id', $categoryIds);
                        }

                        if ($subcategoryIds) {
                            $selection->orWhereIn('training_subcategory_id', $subcategoryIds);
                        }

                        if ($quizIds) {
                            $selection->orWhereIn('id', $quizIds);
                        }
                    });
                }
            });
    }

    /**
     * Sceglie le domande della sfida dando priorita' a quelle che nessuno dei
     * partecipanti ha mai incontrato in una sfida, cosi' col tempo escono
     * quasi tutte. Ritorna null se il pool ha meno domande del necessario.
     *
     * @param  int[]  $userIds
     * @return int[]|null
     */
    public function pickQuestions(?array $categoryIds, array $userIds, ?array $subcategoryIds = null, ?array $quizIds = null): ?array
    {
        $poolIds = $this->poolQuery($categoryIds, $subcategoryIds, $quizIds)->pluck('questions.id')->all();

        if (count($poolIds) < Challenge::QUESTIONS_COUNT) {
            return null;
        }

        $usage = DB::table('challenge_questions as cq')
            ->join('challenge_participants as cp', 'cp.challenge_id', '=', 'cq.challenge_id')
            ->whereIn('cp.user_id', $userIds)
            ->whereIn('cq.question_id', $poolIds)
            ->groupBy('cq.question_id')
            ->selectRaw('cq.question_id, COUNT(*) as uses')
            ->pluck('uses', 'question_id');

        shuffle($poolIds);

        // sort() di PHP 8 e' stabile: a parita' di utilizzi resta l'ordine casuale.
        usort($poolIds, fn ($a, $b) => ($usage[$a] ?? 0) <=> ($usage[$b] ?? 0));

        $picked = array_slice($poolIds, 0, Challenge::QUESTIONS_COUNT);
        shuffle($picked);

        return $picked;
    }

    /**
     * Partecipanti ancora in gioco (invitati che possono giocare o accettati).
     */
    public function activeParticipants(Challenge $challenge): Collection
    {
        return $challenge->participants()->whereIn('status', ['invited', 'accepted'])->get();
    }

    /**
     * Aggiorna lo stato di una sfida aperta (scadenze, inviti live non
     * accettati, chiusura delle live finite). Un invito live che nessuno ha
     * accettato in tempo non va perso: diventa una sfida differita.
     */
    public function refreshStatus(Challenge $challenge): Challenge
    {
        if ($challenge->status !== 'open') {
            return $challenge;
        }

        // Live in corso o appena finita: chiusura lazy, anche se nessuno sta
        // piu' guardando la sala (es. tutti hanno chiuso la pagina).
        if ($challenge->mode === 'live' && $challenge->live_started_at !== null) {
            $timeline = new ChallengeLiveTimeline($challenge);

            if ($timeline->isFinished()) {
                $this->finalizeLiveIfNeeded($challenge, $timeline);
                $challenge->refresh();
            }

            return $challenge;
        }

        if (! $challenge->expires_at->isPast()) {
            return $challenge;
        }

        if ($challenge->mode === 'live') {
            $challenge->update([
                'mode' => 'async',
                'expires_at' => now()->addHours(self::ASYNC_HOURS),
            ]);

            return $challenge;
        }

        $this->closeAtExpiry($challenge);

        return $challenge->refresh();
    }

    /**
     * Alla scadenza della differita: se almeno due giocatori hanno finito la
     * sfida si chiude con loro (gli assenti non contano), altrimenti scade.
     */
    private function closeAtExpiry(Challenge $challenge): void
    {
        $completed = $challenge->attempts()->where('completed', true)->count();

        if ($completed < 2) {
            $challenge->update(['status' => 'expired']);

            return;
        }

        $challenge->participants()
            ->whereIn('status', ['invited', 'accepted'])
            ->whereNotIn('user_id', $challenge->attempts()->where('completed', true)->pluck('user_id'))
            ->update(['status' => 'missed']);

        $this->completeIfReady($challenge);
    }

    /**
     * Registra come "tempo scaduto" le domande live la cui finestra e' gia'
     * chiusa senza risposta, in ordine, cosi' la penalita' si calcola sul
     * punteggio corretto.
     */
    public function syncLiveTimeouts(ChallengeLiveTimeline $timeline, ChallengeAttempt $attempt): void
    {
        $now = now();

        foreach ($timeline->windows() as $window) {
            if ($now->lt($window['ends_at'])) {
                break;
            }

            if ($attempt->answers()->where('question_id', $window['question']->id)->exists()) {
                continue;
            }

            try {
                ChallengeAnswer::create([
                    'challenge_attempt_id' => $attempt->id,
                    'question_id' => $window['question']->id,
                    ...$this->scoreAnswer(
                        $window['question'],
                        null,
                        (int) $window['question']->time_limit_seconds * 1000,
                        null,
                        (int) $attempt->answers()->sum('score')
                    ),
                ]);
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }
    }

    /**
     * A fine timeline chiude i tentativi e la sfida (idempotente).
     */
    public function finalizeLiveIfNeeded(Challenge $challenge, ChallengeLiveTimeline $timeline): void
    {
        if ($challenge->status !== 'open' || ! $timeline->isFinished()) {
            return;
        }

        foreach ($challenge->attempts()->where('completed', false)->get() as $attempt) {
            $this->syncLiveTimeouts($timeline, $attempt);
            $this->closeAttempt($attempt);
        }

        $this->completeIfReady($challenge);
    }

    /**
     * L'ospite si e' registrato: la sua partita diventa un tentativo normale
     * del nuovo account e la sfida si chiude con il risultato. Vale anche se
     * la sfida e' scaduta nel frattempo, purche' l'ospite avesse finito in tempo.
     */
    public function claimGuest(string $token, string $key, User $user): bool
    {
        $guest = ChallengeGuestAttempt::where('guest_key', $key)->whereNull('claimed_by')->whereNotNull('finished_at')->first();
        $challenge = $guest ? Challenge::where('guest_token', $token)->find($guest->challenge_id) : null;

        if (! $guest || ! $challenge || $challenge->challenger_id === $user->id) {
            return false;
        }

        DB::transaction(function () use ($guest, $challenge, $user) {
            if ($challenge->status === 'expired' && $guest->finished_at->lte($challenge->expires_at)) {
                $challenge->update(['status' => 'open']);
            }

            if ($challenge->status !== 'open') {
                $guest->update(['claimed_by' => $user->id]);

                return;
            }

            $challenge->update(['opponent_id' => $user->id]);
            $challenge->participants()->firstOrCreate(
                ['user_id' => $user->id],
                ['status' => 'accepted', 'accepted_at' => $guest->started_at ?? now()]
            );

            $attempt = ChallengeAttempt::firstOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id],
                ['started_at' => $guest->started_at ?? now()]
            );

            foreach ($guest->answers ?? [] as $a) {
                ChallengeAnswer::firstOrCreate(
                    ['challenge_attempt_id' => $attempt->id, 'question_id' => $a['question_id']],
                    collect($a)->only(['answer_id', 'time_taken', 'is_correct', 'is_timeout', 'is_wrong', 'score'])->all()
                );
            }

            $this->closeAttempt($attempt);
            $attempt->update(['finished_at' => $guest->finished_at]);
            $guest->update(['claimed_by' => $user->id]);
        });

        // Se anche chi ha sfidato ha gia' giocato, la sfida si chiude ora.
        $this->completeIfReady($challenge);

        return true;
    }

    /**
     * Calcola e salva punteggio e tempo totale di un tentativo.
     */
    public function closeAttempt(ChallengeAttempt $attempt): void
    {
        $answers = $attempt->answers()->get();

        $attempt->update([
            'score' => (int) $answers->sum('score'),
            'total_time' => (int) $answers->sum('time_taken'),
            'correct_answers' => $answers->where('is_correct', true)->count(),
            'completed' => true,
            'finished_at' => now(),
        ]);
    }

    /**
     * Avvia la sfida live con chi ha accettato: gli invitati che non hanno
     * ancora accettato restano fuori ("missed").
     */
    public function startLive(Challenge $challenge): bool
    {
        return DB::transaction(function () use ($challenge) {
            $locked = Challenge::whereKey($challenge->id)->lockForUpdate()->first();

            if ($locked->status !== 'open' || $locked->mode !== 'live' || $locked->live_started_at !== null) {
                return false;
            }

            $accepted = $locked->participants()->where('status', 'accepted')->get();

            // Servono lo sfidante e almeno un invitato.
            if ($accepted->count() < 2) {
                return false;
            }

            $locked->participants()->where('status', 'invited')->update(['status' => 'missed']);

            $startsAt = now()->addSeconds(self::LIVE_COUNTDOWN_SECONDS);
            $totalSeconds = (int) $locked->questions()->sum('time_limit_seconds');

            // Margine dopo la fine per la chiusura lazy della sfida.
            $locked->update([
                'live_started_at' => $startsAt,
                'expires_at' => $startsAt->copy()->addSeconds($totalSeconds + 120),
            ]);

            foreach ($accepted as $participant) {
                ChallengeAttempt::firstOrCreate(
                    ['challenge_id' => $locked->id, 'user_id' => $participant->user_id],
                    ['started_at' => $startsAt]
                );
            }

            return true;
        });
    }

    /**
     * Dopo un'accettazione o un rifiuto in sala d'attesa: se tutti gli
     * invitati hanno risposto la live parte da sola; se nessuno e' rimasto
     * la sfida viene annullata.
     */
    public function evaluateLobby(Challenge $challenge): void
    {
        if ($challenge->status !== 'open' || $challenge->mode !== 'live' || $challenge->live_started_at !== null) {
            return;
        }

        $invitees = $challenge->participants()->where('user_id', '!=', $challenge->challenger_id)->get();

        if ($invitees->every(fn (ChallengeParticipant $p) => $p->status === 'declined')) {
            $challenge->update(['status' => 'declined']);

            return;
        }

        if ($invitees->where('status', 'invited')->isEmpty()) {
            $this->startLive($challenge);
        }
    }

    /**
     * Dopo il rifiuto di un invitato (differita): se nessun invitato e'
     * rimasto la sfida e' finita, altrimenti potrebbe essere gia' completa.
     */
    public function evaluateAfterDecline(Challenge $challenge): void
    {
        $invitees = $challenge->participants()->where('user_id', '!=', $challenge->challenger_id)->get();

        if ($invitees->every(fn (ChallengeParticipant $p) => $p->status === 'declined')) {
            $challenge->update(['status' => 'declined']);

            return;
        }

        $this->completeIfReady($challenge);
    }

    /**
     * Punteggio di una singola risposta (stessa formula di Quiz e Training).
     * Con $answerId null la risposta vale come tempo scaduto.
     *
     * @return array{answer_id: ?int, time_taken: int, is_correct: bool, is_timeout: bool, is_wrong: bool, score: int}
     */
    public function scoreAnswer(Question $question, ?int $answerId, int $clientTimeMs, ?Carbon $lastEventAt, int $currentScore): array
    {
        $maxTimeMs = (int) $question->time_limit_seconds * 1000;
        $timeTaken = AnswerTimer::resolve($clientTimeMs, $lastEventAt, $maxTimeMs);
        $currentScore = max(0, $currentScore);

        // Fuori tempo (oltre il limite + tolleranza): vale come tempo scaduto.
        if ($answerId !== null && AnswerTimer::isLate($lastEventAt, $maxTimeMs)) {
            $answerId = null;
        }

        if ($answerId === null) {
            return [
                'answer_id' => null,
                'time_taken' => $timeTaken,
                'is_correct' => false,
                'is_timeout' => true,
                'is_wrong' => false,
                'score' => -$this->penalty($currentScore, self::TIMEOUT_PENALTY_RATE),
            ];
        }

        $isCorrect = (bool) $question->answers()->whereKey($answerId)->value('is_correct');

        if ($isCorrect) {
            $speedBonus = (int) round((($maxTimeMs - $timeTaken) / max(1, $maxTimeMs)) * self::MAX_SPEED_BONUS);
            $score = self::CORRECT_ANSWER_BASE_SCORE + $speedBonus;
        } else {
            $score = -$this->penalty($currentScore, self::WRONG_ANSWER_PENALTY_RATE);
        }

        return [
            'answer_id' => $answerId,
            'time_taken' => $timeTaken,
            'is_correct' => $isCorrect,
            'is_timeout' => false,
            'is_wrong' => ! $isCorrect,
            'score' => $score,
        ];
    }

    private function penalty(int $currentScore, float $rate): int
    {
        return min($currentScore, (int) round($currentScore * $rate));
    }

    /**
     * Classifica dei tentativi completati: punteggio piu' alto, a parita'
     * tempo totale minore. Chi e' pari in entrambi condivide la posizione.
     *
     * @return Collection<int, array{attempt: ChallengeAttempt, rank: int}>
     */
    public function ranking(Collection $attempts): Collection
    {
        $sorted = $attempts
            ->where('completed', true)
            ->sort(fn ($a, $b) => [$b->score, $a->total_time] <=> [$a->score, $b->total_time])
            ->values();

        $ranked = collect();
        $previous = null;
        $rank = 0;

        foreach ($sorted as $position => $attempt) {
            if (! $previous || $previous->score !== $attempt->score || $previous->total_time !== $attempt->total_time) {
                $rank = $position + 1;
            }

            $ranked->push(['attempt' => $attempt, 'rank' => $rank]);
            $previous = $attempt;
        }

        return $ranked;
    }

    /**
     * Se tutti i giocatori ancora in gioco hanno finito (e sono almeno due):
     * chiude la sfida, decide il vincitore e assegna gli XP. Idempotente.
     */
    public function completeIfReady(Challenge $challenge): void
    {
        DB::transaction(function () use ($challenge) {
            $locked = Challenge::whereKey($challenge->id)->lockForUpdate()->first();

            if ($locked->status !== 'open') {
                return;
            }

            $completedIds = $locked->attempts()->where('completed', true)->pluck('user_id');
            $stillPlaying = $this->activeParticipants($locked)->pluck('user_id')->diff($completedIds);

            if ($completedIds->count() < 2 || $stillPlaying->isNotEmpty()) {
                return;
            }

            $ranked = $this->ranking($locked->attempts()->get());
            $firstPlace = $ranked->where('rank', 1);

            $winnerId = $firstPlace->count() === 1 ? $firstPlace->first()['attempt']->user_id : null;

            $locked->update([
                'status' => 'completed',
                'completed_at' => now(),
                'winner_id' => $winnerId,
            ]);

            $allIds = $ranked->pluck('attempt.user_id')->all();

            foreach ($ranked as $row) {
                $attempt = $row['attempt'];
                $bonus = 0;

                if ($row['rank'] === 1) {
                    $bonus = $winnerId === null
                        ? (int) config('xp.challenge_draw_bonus')
                        : (int) config('xp.challenge_win_bonus');
                }

                $this->awardXp(
                    $locked,
                    (int) $attempt->user_id,
                    (int) $attempt->score,
                    $bonus,
                    array_values(array_diff($allIds, [$attempt->user_id]))
                );
            }
        });

        $challenge->refresh();
    }

    /**
     * @param  int[]  $otherUserIds
     */
    private function awardXp(Challenge $challenge, int $userId, int $score, int $bonus, array $otherUserIds): void
    {
        $dailyCount = XpEvent::where('user_id', $userId)
            ->where('source', 'challenge')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($dailyCount >= (int) config('xp.challenge_daily_cap')) {
            return;
        }

        // Anti-abuso: serve almeno un avversario con cui oggi non si e' gia'
        // raggiunto il limite di sfide concluse.
        $pairCap = (int) config('xp.challenge_pair_daily_cap');

        $hasFreshOpponent = collect($otherUserIds)->contains(
            fn (int $otherId) => $this->sharedCompletedToday($userId, $otherId, $challenge->id) < $pairCap
        );

        if (! $hasFreshOpponent) {
            return;
        }

        $xp = XpService::fromScore('challenge', $score, Challenge::QUESTIONS_COUNT * self::MAX_SCORE_PER_QUESTION) + $bonus;

        (new XpService())->award($userId, 'challenge', $challenge->id, $xp);
    }

    private function sharedCompletedToday(int $userA, int $userB, int $exceptChallengeId): int
    {
        return Challenge::where('status', 'completed')
            ->where('id', '!=', $exceptChallengeId)
            ->where('completed_at', '>=', now()->startOfDay())
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userA))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userB))
            ->count();
    }
}
