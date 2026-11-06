<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ChallengeInviteMail;
use App\Models\Challenge;
use App\Models\ChallengeAnswer;
use App\Models\ChallengeAttempt;
use App\Models\ChallengeParticipant;
use App\Models\TrainingCategory;
use App\Models\TrainingSubcategory;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\ChallengeLiveTimeline;
use App\Services\ChallengeService;
use App\Services\XpService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChallengeController extends Controller
{
    private const MAX_OPEN_SENT = 5;

    public function __construct(private ChallengeService $service)
    {
    }

    /**
     * Elenco sfide dell'utente: da giocare, inviate in attesa, concluse.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $this->refreshOpenFor($user->id);

        $challenges = Challenge::with(['challenger', 'participants.user', 'attempts'])
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $items = $challenges->map(fn (Challenge $c) => $this->summary($c, $user->id));

        return response()->json([
            'to_play' => $items->where('status', 'open')->where('my_turn', true)->values(),
            'waiting' => $items->where('status', 'open')->where('my_turn', false)->values(),
            'history' => $items->where('status', '!=', 'open')->values(),
        ]);
    }

    /**
     * Contatore leggero per la navbar: sfide aperte in cui tocca a me.
     */
    public function summaryCount(Request $request)
    {
        $user = $request->user();

        $this->refreshOpenFor($user->id);

        $count = Challenge::where('status', 'open')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id)->whereIn('status', ['invited', 'accepted']))
            ->whereDoesntHave('attempts', fn ($a) => $a->where('user_id', $user->id)->where('completed', true))
            ->count();

        return response()->json(['to_play' => $count]);
    }

    /**
     * Categorie Training con il numero di domande usabili nelle sfide.
     */
    public function categories()
    {
        $categories = TrainingCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (TrainingCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'image' => $category->image_url,
                'questions_count' => $this->service->poolQuery([$category->id])->count(),
                // Sottocategorie scegliibili nel dettaglio (con almeno una domanda usabile).
                'subcategories' => $category->subcategories()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($sub) => [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'questions_count' => $this->service->poolQuery(null, [$sub->id])->count(),
                    ])
                    ->filter(fn ($s) => $s['questions_count'] > 0)
                    ->values(),
            ])
            ->filter(fn ($c) => $c['questions_count'] > 0)
            ->values();

        return response()->json([
            'categories' => $categories,
            'all_questions_count' => $this->service->poolQuery(null)->count(),
            'required_questions' => Challenge::QUESTIONS_COUNT,
            'max_invitees' => Challenge::MAX_PARTICIPANTS - 1,
        ]);
    }

    /**
     * Ricerca avversari per nickname: solo utenti verificati che accettano sfide.
     */
    public function searchUsers(Request $request)
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:50']]);

        $term = str_replace(['%', '_'], ['\\%', '\\_'], trim($validated['q']));

        $users = User::whereNotNull('email_verified_at')
            ->where('accepts_challenges', true)
            ->where('id', '!=', $request->user()->id)
            ->where('nickname', 'like', $term.'%')
            ->orderBy('nickname')
            ->limit(8)
            ->get(['id', 'nickname', 'xp_total']);

        return response()->json([
            'users' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'nickname' => $u->nickname,
                'level' => XpService::info((int) $u->xp_total)['level'],
            ]),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate(['accepts_challenges' => ['required', 'boolean']]);

        $request->user()->forceFill(['accepts_challenges' => $validated['accepts_challenges']])->save();

        return response()->json(['accepts_challenges' => (bool) $validated['accepts_challenges']]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'opponent_ids' => ['required', 'array', 'min:1', 'max:'.(Challenge::MAX_PARTICIPANTS - 1)],
            'opponent_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:training_categories,id'],
            'subcategory_ids' => ['nullable', 'array'],
            'subcategory_ids.*' => ['integer', 'exists:training_subcategories,id'],
            'mode' => ['nullable', 'in:async,live'],
        ]);

        $mode = $validated['mode'] ?? 'async';
        $user = $request->user();

        $opponents = User::whereIn('id', $validated['opponent_ids'])->get();

        foreach ($opponents as $opponent) {
            if ($opponent->id === $user->id) {
                return response()->json(['message' => 'Non puoi sfidare te stesso'], 422);
            }

            if (! $opponent->hasVerifiedEmail() || ! $opponent->accepts_challenges) {
                return response()->json(['message' => $opponent->nickname.' non puo ricevere sfide'], 422);
            }
        }

        $this->refreshOpenFor($user->id);

        if (Challenge::where('challenger_id', $user->id)->where('status', 'open')->count() >= self::MAX_OPEN_SENT) {
            return response()->json(['message' => 'Hai gia troppe sfide in attesa: aspetta che vengano giocate'], 422);
        }

        foreach ($opponents as $opponent) {
            $alreadyOpen = Challenge::where('status', 'open')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $opponent->id))
                ->exists();

            if ($alreadyOpen) {
                return response()->json(['message' => 'Hai gia una sfida aperta con '.$opponent->nickname], 422);
            }
        }

        $categoryIds = ! empty($validated['category_ids'])
            ? array_values(array_unique(array_map('intval', $validated['category_ids'])))
            : null;

        // Una categoria scelta in generale include gia' tutte le sue sottocategorie.
        $subcategoryIds = ! empty($validated['subcategory_ids'])
            ? TrainingSubcategory::whereIn('id', $validated['subcategory_ids'])
                ->whereNotIn('training_category_id', $categoryIds ?? [])
                ->pluck('id')
                ->all()
            : [];
        $subcategoryIds = $subcategoryIds ?: null;

        $questionIds = $this->service->pickQuestions($categoryIds, [$user->id, ...$opponents->pluck('id')->all()], $subcategoryIds);

        if ($questionIds === null) {
            return response()->json([
                'message' => 'Le categorie scelte non hanno abbastanza domande per una sfida: aggiungine altre',
            ], 422);
        }

        $challenge = DB::transaction(function () use ($user, $opponents, $categoryIds, $subcategoryIds, $questionIds, $mode) {
            $challenge = Challenge::create([
                'challenger_id' => $user->id,
                'opponent_id' => $opponents->first()->id,
                'mode' => $mode,
                'status' => 'open',
                'category_ids' => $categoryIds,
                'subcategory_ids' => $subcategoryIds,
                'expires_at' => $mode === 'live'
                    ? now()->addMinutes(ChallengeService::LIVE_INVITE_MINUTES)
                    : now()->addHours(ChallengeService::ASYNC_HOURS),
            ]);

            $challenge->participants()->create([
                'user_id' => $user->id,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            foreach ($opponents as $opponent) {
                $challenge->participants()->create(['user_id' => $opponent->id, 'status' => 'invited']);
            }

            $rows = [];
            foreach ($questionIds as $position => $questionId) {
                $rows[] = [
                    'challenge_id' => $challenge->id,
                    'question_id' => $questionId,
                    'position' => $position + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('challenge_questions')->insert($rows);

            return $challenge;
        });

        $this->sendInvites($challenge, $user, $opponents->all());

        return response()->json(['id' => $challenge->id], 201);
    }

    /**
     * Le email partono subito dopo la risposta all'utente (non rallentano la
     * richiesta e non aspettano nessun worker): una per ogni invitato.
     *
     * @param  User[]  $opponents
     */
    private function sendInvites(Challenge $challenge, User $challenger, array $opponents): void
    {
        $challenge->setRelation('challenger', $challenger);

        app()->terminating(function () use ($challenge, $opponents) {
            foreach ($opponents as $opponent) {
                try {
                    $others = collect($opponents)->where('id', '!=', $opponent->id)->pluck('nickname')->all();

                    Mail::to($opponent->email)->send(new ChallengeInviteMail($challenge, $opponent, $others));
                } catch (\Throwable $e) {
                    // L'email e' un di piu': la sfida resta valida anche se l'invio fallisce.
                    Log::warning('Invio email sfida fallito: '.$e->getMessage());
                }
            }
        });
    }

    public function show(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        $challenge->load(['challenger', 'participants.user', 'attempts']);

        return response()->json($this->detail($challenge, $user->id));
    }

    /**
     * Lo sfidato rifiuta, lo sfidante annulla: possibile solo finche' gli
     * altri non hanno iniziato a giocare.
     */
    public function decline(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if ($challenge->status !== 'open') {
            return response()->json(['message' => 'Sfida non piu aperta'], 422);
        }

        if ($challenge->isChallenger($user->id)) {
            $othersStarted = $challenge->live_started_at !== null
                || $challenge->attempts()->where('user_id', '!=', $user->id)->exists();

            if ($othersStarted) {
                return response()->json(['message' => 'Gli altri giocatori hanno gia iniziato la sfida'], 422);
            }

            $challenge->update(['status' => 'cancelled']);

            return response()->json(['status' => 'cancelled']);
        }

        $participant = $challenge->participants()->where('user_id', $user->id)->firstOrFail();

        if (! $participant->isActive() || $challenge->attempts()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Hai gia iniziato questa sfida'], 422);
        }

        $participant->update(['status' => 'declined']);

        if ($challenge->mode === 'live' && $challenge->live_started_at === null) {
            $this->service->evaluateLobby($challenge);
        } else {
            $this->service->evaluateAfterDecline($challenge);
        }

        return response()->json(['status' => $challenge->refresh()->status]);
    }

    /**
     * Inizia (o riprende) la propria partita differita. Le domande non
     * contengono mai l'indicazione della risposta corretta.
     */
    public function start(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if ($challenge->status !== 'open') {
            return response()->json(['message' => 'Questa sfida non e piu giocabile'], 403);
        }

        if ($challenge->mode === 'live') {
            return response()->json(['message' => 'Questa e una sfida live: entra dalla sala live'], 403);
        }

        $participant = $this->activeParticipant($challenge, $user->id);

        if (! $participant) {
            return response()->json(['message' => 'Non puoi piu giocare questa sfida'], 403);
        }

        try {
            $attempt = ChallengeAttempt::firstOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id],
                ['started_at' => now()]
            );
        } catch (UniqueConstraintViolationException) {
            $attempt = ChallengeAttempt::where('challenge_id', $challenge->id)->where('user_id', $user->id)->firstOrFail();
        }

        if ($participant->status === 'invited') {
            $participant->update(['status' => 'accepted', 'accepted_at' => now()]);
        }

        if ($attempt->completed) {
            return response()->json(['message' => 'Hai gia giocato questa sfida'], 403);
        }

        $questions = $challenge->questions()->with(['answers', 'quiz.trainingCategory', 'quiz.trainingSubcategory'])->get();

        return response()->json([
            'challenge_id' => $challenge->id,
            'total_questions' => $questions->count(),
            'answered_question_ids' => $attempt->answers()->pluck('question_id')->all(),
            'questions' => $questions->map(fn ($question) => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'image' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                'audio' => $question->resolvedAudioUrl(),
                'audio_start_seconds' => $question->audio_start_seconds,
                'audio_end_seconds' => $question->audio_end_seconds,
                'video' => $question->video_path ? asset('storage/'.$question->video_path) : null,
                'time_limit_seconds' => $question->time_limit_seconds,
                'category' => $question->quiz?->trainingCategory?->name,
                'subcategory' => $question->quiz?->trainingSubcategory?->name,
                'answers' => $question->answers->shuffle()->map(fn ($answer) => [
                    'id' => $answer->id,
                    'answer_text' => $answer->answer_text,
                ])->values(),
            ])->values(),
        ]);
    }

    /**
     * Registra una risposta. Non rivela mai se era giusta: serve a evitare
     * che le soluzioni escano prima della chiusura della sfida.
     */
    public function answer(Request $request, Challenge $challenge)
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'answer_id' => ['nullable', 'integer'],
            'time_taken' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        $attempt = ChallengeAttempt::where('challenge_id', $challenge->id)->where('user_id', $user->id)->first();

        if ($challenge->status !== 'open' || $challenge->mode === 'live' || ! $attempt || $attempt->completed) {
            return response()->json(['message' => 'Tentativo non valido'], 403);
        }

        $question = $challenge->questions()->where('questions.id', $validated['question_id'])->first();

        if (! $question) {
            return response()->json(['message' => 'Domanda non valida per questa sfida'], 422);
        }

        if ($attempt->answers()->where('question_id', $question->id)->exists()) {
            return response()->json(['message' => 'Hai gia risposto a questa domanda'], 403);
        }

        $answerId = $validated['answer_id'] ?? null;

        if ($answerId !== null && ! $question->answers()->whereKey($answerId)->exists()) {
            return response()->json(['message' => 'Risposta non valida'], 422);
        }

        $lastAnsweredAt = $attempt->answers()->max('created_at');
        $lastEventAt = $lastAnsweredAt ? Carbon::parse($lastAnsweredAt) : $attempt->started_at;

        $scored = $this->service->scoreAnswer(
            $question,
            $answerId,
            (int) $validated['time_taken'],
            $lastEventAt,
            (int) $attempt->answers()->sum('score')
        );

        try {
            ChallengeAnswer::create([
                'challenge_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                ...$scored,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Hai gia risposto a questa domanda'], 403);
        }

        return response()->json([
            'recorded' => true,
            'answered_count' => $attempt->answers()->count(),
        ]);
    }

    public function finish(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        $attempt = ChallengeAttempt::where('challenge_id', $challenge->id)->where('user_id', $user->id)->first();

        if ($challenge->status !== 'open' || $challenge->mode === 'live' || ! $attempt || $attempt->completed) {
            return response()->json(['message' => 'Tentativo non valido'], 403);
        }

        $questions = $challenge->questions()->get();

        // Domande non risposte (es. pagina chiusa): contano come tempo scaduto.
        foreach ($questions as $question) {
            if ($attempt->answers()->where('question_id', $question->id)->exists()) {
                continue;
            }

            try {
                ChallengeAnswer::create([
                    'challenge_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    ...$this->service->scoreAnswer(
                        $question,
                        null,
                        (int) $question->time_limit_seconds * 1000,
                        null,
                        (int) $attempt->answers()->sum('score')
                    ),
                ]);
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        $this->service->closeAttempt($attempt);
        $this->service->completeIfReady($challenge);

        $challenge->refresh()->load(['challenger', 'participants.user', 'attempts']);

        return response()->json([
            'completed' => true,
            ...$this->detail($challenge, $user->id),
        ]);
    }

    /**
     * Confronto domanda per domanda. Disponibile solo a sfida chiusa.
     */
    public function review(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if (! $challenge->isClosed()) {
            return response()->json([
                'message' => 'Le risposte saranno visibili quando la sfida sara conclusa',
            ], 403);
        }

        $challenge->load(['challenger', 'participants.user', 'attempts.answers']);

        $questions = $challenge->questions()->with(['answers', 'quiz.trainingCategory', 'quiz.trainingSubcategory'])->get();

        return response()->json([
            ...$this->detail($challenge, $user->id),
            'questions' => $questions->map(function ($question) use ($challenge) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'category' => $question->quiz?->trainingCategory?->name,
                    'subcategory' => $question->quiz?->trainingSubcategory?->name,
                    'image' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                    'answers' => $question->answers->map(fn ($a) => [
                        'id' => $a->id,
                        'answer_text' => $a->answer_text,
                        'is_correct' => (bool) $a->is_correct,
                    ])->values(),
                    'results' => $challenge->attempts->map(function (ChallengeAttempt $attempt) use ($question) {
                        $row = $this->answerRow($attempt->answers->firstWhere('question_id', $question->id));

                        return $row ? ['user_id' => $attempt->user_id, ...$row] : null;
                    })->filter()->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Un invitato accetta la sfida live. Quando tutti gli invitati hanno
     * risposto la partita parte da sola.
     */
    public function liveAccept(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if ($challenge->mode !== 'live' || $challenge->status !== 'open' || $challenge->live_started_at !== null) {
            return response()->json(['message' => 'Questa sfida live non e piu disponibile'], 422);
        }

        if ($challenge->isChallenger($user->id)) {
            return response()->json(['message' => 'Sei gia dentro: avvia la sfida quando vuoi'], 403);
        }

        $participant = $challenge->participants()->where('user_id', $user->id)->firstOrFail();

        if ($participant->status !== 'invited') {
            return response()->json(['message' => 'Non puoi accettare questa sfida'], 422);
        }

        $participant->update(['status' => 'accepted', 'accepted_at' => now()]);

        $this->service->evaluateLobby($challenge);

        return response()->json([
            'accepted' => true,
            'started' => $challenge->refresh()->live_started_at !== null,
        ]);
    }

    /**
     * Lo sfidante avvia subito la live con chi ha gia accettato.
     */
    public function liveStart(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if (! $challenge->isChallenger($user->id)) {
            return response()->json(['message' => 'Solo chi ha lanciato la sfida puo avviarla'], 403);
        }

        if (! $this->service->startLive($challenge)) {
            return response()->json(['message' => 'Serve almeno un giocatore che ha accettato'], 422);
        }

        return response()->json(['started' => true]);
    }

    /**
     * Stato della sala live (polling): attesa, conto alla rovescia, domanda
     * corrente o fine. Non rivela mai l'esito delle risposte.
     */
    public function liveState(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);
        $challenge->refresh()->load(['challenger', 'participants.user', 'attempts']);

        $base = [
            'server_time' => now()->toISOString(),
            'challenge' => $this->summary($challenge, $user->id),
        ];

        if ($challenge->status === 'completed') {
            return response()->json([...$base, 'phase' => 'finished']);
        }

        if ($challenge->status !== 'open') {
            return response()->json([...$base, 'phase' => 'closed']);
        }

        if ($challenge->mode !== 'live') {
            return response()->json([...$base, 'phase' => 'converted']);
        }

        $me = $challenge->participants->firstWhere('user_id', $user->id);

        if (! $me->isActive()) {
            return response()->json([...$base, 'phase' => 'missed']);
        }

        $roster = $challenge->participants->map(fn (ChallengeParticipant $p) => [
            ...$this->playerPayload($p->user),
            'status' => $p->status,
            'is_me' => (int) $p->user_id === $user->id,
        ])->values();

        if ($challenge->live_started_at === null) {
            $acceptedInvitees = $challenge->participants
                ->where('status', 'accepted')
                ->where('user_id', '!=', $challenge->challenger_id)
                ->count();

            return response()->json([
                ...$base,
                'phase' => 'lobby',
                'role' => $challenge->isChallenger($user->id) ? 'challenger' : 'invitee',
                'my_status' => $me->status,
                'players' => $roster,
                'can_start' => $challenge->isChallenger($user->id) && $acceptedInvitees >= 1,
                'expires_at' => $challenge->expires_at->toISOString(),
            ]);
        }

        $timeline = new ChallengeLiveTimeline($challenge);

        if (! $timeline->hasStarted()) {
            return response()->json([
                ...$base,
                'phase' => 'countdown',
                'players' => $roster->where('status', 'accepted')->values(),
                'starting_in_ms' => (int) now()->diffInMilliseconds($challenge->live_started_at),
            ]);
        }

        $myAttempt = $challenge->attempts->firstWhere('user_id', $user->id);

        if (! $myAttempt) {
            return response()->json([...$base, 'phase' => 'missed']);
        }

        $window = $timeline->currentWindow();

        if (! $window) {
            // Tutte le finestre chiuse: refreshStatus ha gia' provato a chiudere.
            return response()->json([...$base, 'phase' => 'finishing']);
        }

        foreach ($challenge->attempts as $attempt) {
            $this->service->syncLiveTimeouts($timeline, $attempt);
        }

        $question = $window['question'];
        $others = $challenge->attempts->where('user_id', '!=', $user->id);

        $answeredOthers = $others->filter(
            fn (ChallengeAttempt $a) => $a->answers()->where('question_id', $question->id)->where('is_timeout', false)->exists()
        )->count();

        return response()->json([
            ...$base,
            'phase' => 'question',
            'total_questions' => $timeline->questions()->count(),
            'question' => [
                'index' => $window['index'],
                'id' => $question->id,
                'question_text' => $question->question_text,
                'image' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                'audio' => $question->resolvedAudioUrl(),
                'audio_start_seconds' => $question->audio_start_seconds,
                'audio_end_seconds' => $question->audio_end_seconds,
                'video' => $question->video_path ? asset('storage/'.$question->video_path) : null,
                'starts_at' => $window['starts_at']->toISOString(),
                'ends_at' => $window['ends_at']->toISOString(),
                'category' => $question->quiz?->trainingCategory?->name,
                'subcategory' => $question->quiz?->trainingSubcategory?->name,
                'answers' => $question->answers->map(fn ($a) => [
                    'id' => $a->id,
                    'answer_text' => $a->answer_text,
                ])->values(),
            ],
            'has_answered' => $myAttempt->answers()->where('question_id', $question->id)->where('is_timeout', false)->exists(),
            'others_answered' => $answeredOthers,
            'others_total' => $others->count(),
        ]);
    }

    /**
     * Risposta live: il tempo lo misura il server dall'inizio della finestra
     * della domanda, non il client.
     */
    public function liveAnswer(Request $request, Challenge $challenge)
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'answer_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if ($challenge->status !== 'open' || $challenge->mode !== 'live' || $challenge->live_started_at === null) {
            return response()->json(['message' => 'Sfida live non attiva'], 403);
        }

        $timeline = new ChallengeLiveTimeline($challenge);
        $window = $timeline->currentWindow();

        if (! $window || (int) $window['question']->id !== (int) $validated['question_id']) {
            return response()->json(['message' => 'Tempo scaduto per questa domanda'], 403);
        }

        $attempt = ChallengeAttempt::where('challenge_id', $challenge->id)->where('user_id', $user->id)->first();

        if (! $attempt || $attempt->completed) {
            return response()->json(['message' => 'Tentativo non valido'], 403);
        }

        $question = $window['question'];

        if (! $question->answers->contains('id', (int) $validated['answer_id'])) {
            return response()->json(['message' => 'Risposta non valida'], 422);
        }

        $this->service->syncLiveTimeouts($timeline, $attempt);

        if ($attempt->answers()->where('question_id', $question->id)->exists()) {
            return response()->json(['message' => 'Hai gia risposto a questa domanda'], 403);
        }

        $elapsedMs = (int) $window['starts_at']->diffInMilliseconds(now());

        $scored = $this->service->scoreAnswer(
            $question,
            (int) $validated['answer_id'],
            $elapsedMs,
            null,
            (int) $attempt->answers()->sum('score')
        );

        try {
            ChallengeAnswer::create([
                'challenge_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                ...$scored,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Hai gia risposto a questa domanda'], 403);
        }

        return response()->json(['recorded' => true]);
    }

    private function answerRow(?ChallengeAnswer $answer): ?array
    {
        if (! $answer) {
            return null;
        }

        return [
            'answer_id' => $answer->answer_id,
            'is_correct' => $answer->is_correct,
            'is_timeout' => $answer->is_timeout,
            'time_taken' => $answer->time_taken,
            'score' => $answer->score,
        ];
    }

    private function authorizeParticipant(Challenge $challenge, int $userId): void
    {
        abort_unless($challenge->involves($userId), 404);
    }

    private function activeParticipant(Challenge $challenge, int $userId): ?ChallengeParticipant
    {
        $participant = $challenge->participants()->where('user_id', $userId)->first();

        return $participant && $participant->isActive() ? $participant : null;
    }

    /**
     * Aggiorna lo stato delle sfide aperte dell'utente (scadenze, inviti live
     * non accettati, chiusura delle live finite).
     */
    private function refreshOpenFor(int $userId): void
    {
        Challenge::where('status', 'open')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->each(fn (Challenge $c) => $this->service->refreshStatus($c));
    }

    private function categoryNames(Challenge $challenge): array
    {
        return $challenge->selectionLabels();
    }

    private function playerPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'level' => XpService::info((int) $user->xp_total)['level'],
        ];
    }

    private function summary(Challenge $challenge, int $userId): array
    {
        $me = $challenge->participants->firstWhere('user_id', $userId);
        $myAttempt = $challenge->attempts->firstWhere('user_id', $userId);
        $closed = $challenge->isClosed();

        $others = $challenge->participants
            ->where('user_id', '!=', $userId)
            ->map(fn (ChallengeParticipant $p) => [...$this->playerPayload($p->user), 'status' => $p->status])
            ->values();

        $result = null;
        $myRank = null;

        if ($challenge->status === 'completed') {
            $ranked = $this->service->ranking($challenge->attempts);
            $myRank = $ranked->first(fn ($row) => (int) $row['attempt']->user_id === $userId)['rank'] ?? null;

            if ($myRank === 1) {
                $result = $challenge->winner_id === null ? 'draw' : 'won';
            } elseif ($myRank !== null) {
                $result = 'lost';
            }
        }

        $myTurn = $challenge->status === 'open'
            && $me
            && $me->isActive()
            && ! ($myAttempt?->completed);

        return [
            'id' => $challenge->id,
            'status' => $challenge->status,
            'mode' => $challenge->mode,
            'direction' => $challenge->isChallenger($userId) ? 'sent' : 'received',
            'challenger' => $this->playerPayload($challenge->challenger),
            'others' => $others,
            'participants_count' => $challenge->participants->count(),
            'expires_at' => $challenge->expires_at->toISOString(),
            'my_turn' => (bool) $myTurn,
            'my_started' => (bool) $myAttempt,
            'live_started' => $challenge->live_started_at !== null,
            'result' => $closed ? $result : null,
            'my_rank' => $myRank,
            'categories' => $this->categoryNames($challenge),
        ];
    }

    private function detail(Challenge $challenge, int $userId): array
    {
        $payload = $this->summary($challenge, $userId);
        $closed = $challenge->isClosed();
        $ranked = $closed ? $this->service->ranking($challenge->attempts) : collect();

        $players = $challenge->participants->map(function (ChallengeParticipant $p) use ($challenge, $userId, $closed, $ranked) {
            $attempt = $challenge->attempts->firstWhere('user_id', $p->user_id);

            $row = [
                ...$this->playerPayload($p->user),
                'status' => $p->status,
                'is_me' => (int) $p->user_id === $userId,
                'is_challenger' => $challenge->isChallenger((int) $p->user_id),
                'started' => (bool) $attempt,
                'completed' => (bool) $attempt?->completed,
            ];

            // I punteggi (anche il proprio) restano nascosti finche' la sfida e'
            // aperta: il totale rivelerebbe quante risposte sono giuste.
            if ($closed && $attempt?->completed) {
                $row['score'] = $attempt->score;
                $row['total_time'] = $attempt->total_time;
                $row['correct_answers'] = $attempt->correct_answers;
                $row['rank'] = $ranked->first(fn ($r) => (int) $r['attempt']->user_id === (int) $p->user_id)['rank'] ?? null;
            }

            return $row;
        })->values();

        $payload['players'] = $closed
            ? $players->sortBy(fn ($p) => $p['rank'] ?? 99)->values()
            : $players;
        $payload['me'] = $players->firstWhere('is_me', true);
        $payload['total_questions'] = Challenge::QUESTIONS_COUNT;

        $iAmChallenger = $challenge->isChallenger($userId);
        $payload['can_give_up'] = $challenge->status === 'open'
            && ($iAmChallenger
                ? $challenge->live_started_at === null && ! $challenge->attempts->where('user_id', '!=', $userId)->count()
                : ($payload['me']['status'] ?? null) === 'invited' && ! $payload['me']['started']);

        if ($closed) {
            $payload['xp_awarded'] = (int) XpEvent::where('user_id', $userId)
                ->where('source', 'challenge')
                ->where('source_id', $challenge->id)
                ->value('xp');
            $payload['xp'] = XpService::info((int) User::whereKey($userId)->value('xp_total'));
        }

        return $payload;
    }
}
