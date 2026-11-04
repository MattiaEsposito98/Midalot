<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ChallengeInviteMail;
use App\Models\Challenge;
use App\Models\ChallengeAnswer;
use App\Models\ChallengeAttempt;
use App\Models\TrainingCategory;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\ChallengeService;
use App\Services\XpService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChallengeController extends Controller
{
    private const EXPIRES_AFTER_HOURS = 72;

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

        $this->expireOpenFor($user->id);

        $challenges = Challenge::with(['challenger', 'opponent', 'attempts'])
            ->where(fn ($q) => $q->where('challenger_id', $user->id)->orWhere('opponent_id', $user->id))
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

        $this->expireOpenFor($user->id);

        $count = Challenge::where('status', 'open')
            ->where(fn ($q) => $q->where('challenger_id', $user->id)->orWhere('opponent_id', $user->id))
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
            ])
            ->filter(fn ($c) => $c['questions_count'] > 0)
            ->values();

        return response()->json([
            'categories' => $categories,
            'all_questions_count' => $this->service->poolQuery(null)->count(),
            'required_questions' => Challenge::QUESTIONS_COUNT,
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
            'opponent_id' => ['required', 'integer', 'exists:users,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:training_categories,id'],
        ]);

        $user = $request->user();
        $opponent = User::findOrFail($validated['opponent_id']);

        if ($opponent->id === $user->id) {
            return response()->json(['message' => 'Non puoi sfidare te stesso'], 422);
        }

        if (! $opponent->hasVerifiedEmail() || ! $opponent->accepts_challenges) {
            return response()->json(['message' => 'Questo utente non puo ricevere sfide'], 422);
        }

        $this->expireOpenFor($user->id);

        if (Challenge::where('challenger_id', $user->id)->where('status', 'open')->count() >= self::MAX_OPEN_SENT) {
            return response()->json(['message' => 'Hai gia troppe sfide in attesa: aspetta che vengano giocate'], 422);
        }

        $alreadyOpen = Challenge::where('status', 'open')
            ->where(function ($q) use ($user, $opponent) {
                $q->where(fn ($x) => $x->where('challenger_id', $user->id)->where('opponent_id', $opponent->id))
                    ->orWhere(fn ($x) => $x->where('challenger_id', $opponent->id)->where('opponent_id', $user->id));
            })
            ->exists();

        if ($alreadyOpen) {
            return response()->json(['message' => 'Hai gia una sfida aperta con questo utente'], 422);
        }

        $categoryIds = ! empty($validated['category_ids'])
            ? array_values(array_unique(array_map('intval', $validated['category_ids'])))
            : null;

        $questionIds = $this->service->pickQuestions($categoryIds, $user->id, $opponent->id);

        if ($questionIds === null) {
            return response()->json([
                'message' => 'Le categorie scelte non hanno abbastanza domande per una sfida: aggiungine altre',
            ], 422);
        }

        $challenge = DB::transaction(function () use ($user, $opponent, $categoryIds, $questionIds) {
            $challenge = Challenge::create([
                'challenger_id' => $user->id,
                'opponent_id' => $opponent->id,
                'mode' => 'async',
                'status' => 'open',
                'category_ids' => $categoryIds,
                'expires_at' => now()->addHours(self::EXPIRES_AFTER_HOURS),
            ]);

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

        try {
            $challenge->load(['challenger', 'opponent']);
            Mail::to($opponent->email)->send(new ChallengeInviteMail($challenge));
        } catch (\Throwable $e) {
            // L'email e' un di piu': la sfida resta valida anche se l'invio fallisce.
            Log::warning('Invio email sfida fallito: '.$e->getMessage());
        }

        return response()->json(['id' => $challenge->id], 201);
    }

    public function show(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        $challenge->load(['challenger', 'opponent', 'attempts']);

        return response()->json($this->detail($challenge, $user->id));
    }

    public function decline(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if ($challenge->status !== 'open') {
            return response()->json(['message' => 'Sfida non piu aperta'], 422);
        }

        // Lo sfidato puo' rifiutare, lo sfidante annullare: in entrambi i casi
        // solo se l'altro non ha ancora iniziato a giocare.
        $otherStarted = $challenge->attempts()
            ->where('user_id', $challenge->otherUserId($user->id))
            ->exists();

        if ($otherStarted) {
            return response()->json(['message' => 'L\'altro giocatore ha gia iniziato la sfida'], 422);
        }

        $challenge->update([
            'status' => (int) $challenge->opponent_id === $user->id ? 'declined' : 'cancelled',
        ]);

        return response()->json(['status' => $challenge->status]);
    }

    /**
     * Inizia (o riprende) la propria partita. Le domande non contengono mai
     * l'indicazione della risposta corretta.
     */
    public function start(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        $this->authorizeParticipant($challenge, $user->id);
        $this->service->refreshStatus($challenge);

        if ($challenge->status !== 'open') {
            return response()->json(['message' => 'Questa sfida non e piu giocabile'], 403);
        }

        try {
            $attempt = ChallengeAttempt::firstOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id],
                ['started_at' => now()]
            );
        } catch (UniqueConstraintViolationException) {
            $attempt = ChallengeAttempt::where('challenge_id', $challenge->id)->where('user_id', $user->id)->firstOrFail();
        }

        if ($attempt->completed) {
            return response()->json(['message' => 'Hai gia giocato questa sfida'], 403);
        }

        $questions = $challenge->questions()->with('answers')->get();

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

        if ($challenge->status !== 'open' || ! $attempt || $attempt->completed) {
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

        if ($challenge->status !== 'open' || ! $attempt || $attempt->completed) {
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

        $answers = $attempt->answers()->get();

        $attempt->update([
            'score' => (int) $answers->sum('score'),
            'total_time' => (int) $answers->sum('time_taken'),
            'correct_answers' => $answers->where('is_correct', true)->count(),
            'completed' => true,
            'finished_at' => now(),
        ]);

        $this->service->completeIfReady($challenge);
        $challenge->refresh()->load(['challenger', 'opponent', 'attempts']);

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

        $challenge->load(['challenger', 'opponent', 'attempts.answers']);

        $mine = $challenge->attempts->firstWhere('user_id', $user->id);
        $theirs = $challenge->attempts->firstWhere('user_id', $challenge->otherUserId($user->id));

        $questions = $challenge->questions()->with('answers')->get();

        return response()->json([
            ...$this->detail($challenge, $user->id),
            'questions' => $questions->map(function ($question) use ($mine, $theirs) {
                $myAnswer = $mine?->answers->firstWhere('question_id', $question->id);
                $theirAnswer = $theirs?->answers->firstWhere('question_id', $question->id);

                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'image' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                    'answers' => $question->answers->map(fn ($a) => [
                        'id' => $a->id,
                        'answer_text' => $a->answer_text,
                        'is_correct' => (bool) $a->is_correct,
                    ])->values(),
                    'mine' => $this->answerRow($myAnswer),
                    'theirs' => $this->answerRow($theirAnswer),
                ];
            })->values(),
        ]);
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

    private function expireOpenFor(int $userId): void
    {
        Challenge::where('status', 'open')
            ->where('expires_at', '<', now())
            ->where(fn ($q) => $q->where('challenger_id', $userId)->orWhere('opponent_id', $userId))
            ->update(['status' => 'expired']);
    }

    private function categoryNames(Challenge $challenge): array
    {
        if (empty($challenge->category_ids)) {
            return ['Tutte le categorie'];
        }

        return TrainingCategory::whereIn('id', $challenge->category_ids)->orderBy('name')->pluck('name')->all();
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
        $other = (int) $challenge->challenger_id === $userId ? $challenge->opponent : $challenge->challenger;
        $myAttempt = $challenge->attempts->firstWhere('user_id', $userId);
        $closed = $challenge->isClosed();

        return [
            'id' => $challenge->id,
            'status' => $challenge->status,
            'mode' => $challenge->mode,
            'direction' => (int) $challenge->challenger_id === $userId ? 'sent' : 'received',
            'other' => $this->playerPayload($other),
            'expires_at' => $challenge->expires_at->toISOString(),
            'my_turn' => $challenge->status === 'open' && ! ($myAttempt?->completed),
            'my_started' => (bool) $myAttempt,
            'result' => $closed && $challenge->status === 'completed'
                ? ($challenge->winner_id === null ? 'draw' : ((int) $challenge->winner_id === $userId ? 'won' : 'lost'))
                : null,
            'categories' => $this->categoryNames($challenge),
        ];
    }

    private function detail(Challenge $challenge, int $userId): array
    {
        $payload = $this->summary($challenge, $userId);

        $mine = $challenge->attempts->firstWhere('user_id', $userId);
        $theirs = $challenge->attempts->firstWhere('user_id', $challenge->otherUserId($userId));

        $payload['me'] = [
            'completed' => (bool) $mine?->completed,
        ];
        $payload['opponent_state'] = [
            'started' => (bool) $theirs,
            'completed' => (bool) $theirs?->completed,
        ];
        $payload['total_questions'] = Challenge::QUESTIONS_COUNT;

        // I punteggi (anche il proprio) restano nascosti finche' la sfida e'
        // aperta: il totale rivelerebbe quante risposte sono giuste.
        if ($challenge->isClosed()) {
            $payload['me']['score'] = $mine?->score;
            $payload['me']['total_time'] = $mine?->total_time;
            $payload['me']['correct_answers'] = $mine?->correct_answers;
            $payload['opponent_state']['score'] = $theirs?->score;
            $payload['opponent_state']['total_time'] = $theirs?->total_time;
            $payload['opponent_state']['correct_answers'] = $theirs?->correct_answers;
            $payload['xp_awarded'] = (int) XpEvent::where('user_id', $userId)
                ->where('source', 'challenge')
                ->where('source_id', $challenge->id)
                ->value('xp');
            $payload['xp'] = XpService::info((int) User::whereKey($userId)->value('xp_total'));
        }

        return $payload;
    }
}
