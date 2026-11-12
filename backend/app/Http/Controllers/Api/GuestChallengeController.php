<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GuestChallengeMail;
use App\Models\Challenge;
use App\Models\ChallengeGuestAttempt;
use App\Models\User;
use App\Services\ChallengeService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Sfida a un amico non registrato. Chi sfida crea la sfida con un link
 * (ChallengeController::store con guest=true); l'ospite la gioca senza
 * account da queste rotte pubbliche e, per vedere il risultato, si registra.
 */
class GuestChallengeController extends Controller
{
    public function __construct(private ChallengeService $service, private ReferralService $referrals) {}

    private function challenge(string $token): Challenge
    {
        return Challenge::where('guest_token', $token)->with('challenger')->firstOrFail();
    }

    private function attempt(Challenge $challenge, ?string $key): ?ChallengeGuestAttempt
    {
        if (! $key) {
            return null;
        }

        return ChallengeGuestAttempt::where('challenge_id', $challenge->id)->where('guest_key', $key)->first();
    }

    /**
     * Pagina d'invito: chi ti sfida, su cosa e se si puo' ancora giocare.
     */
    public function show(Request $request, string $token)
    {
        $challenge = $this->challenge($token);
        $this->service->refreshStatus($challenge);
        $mine = $this->attempt($challenge, $request->query('key'));
        $taken = ChallengeGuestAttempt::where('challenge_id', $challenge->id)->whereNotNull('finished_at')->exists();

        return response()->json([
            'challenger' => [
                'nickname' => $challenge->challenger->nickname,
                ...$challenge->challenger->publicAvatar(),
            ],
            'guest_name' => $challenge->guest_name,
            'categories' => $challenge->category_ids
                ? DB::table('training_categories')->whereIn('id', $challenge->category_ids)->pluck('name')
                : [],
            'total_questions' => Challenge::QUESTIONS_COUNT,
            'expires_at' => $challenge->expires_at->toISOString(),
            'playable' => $challenge->status === 'open' && ! $challenge->participants()->where('user_id', '!=', $challenge->challenger_id)->exists() && (! $taken || ($mine && ! $mine->finished_at)),
            'finished' => (bool) $mine?->finished_at,
            'result' => $mine?->finished_at ? $this->result($challenge, $mine) : null,
            'ref' => $this->referrals->code($challenge->challenger),
            'welcome_coins' => (int) config('referral.welcome_coins'),
        ]);
    }

    public function start(Request $request, string $token)
    {
        $challenge = $this->challenge($token);
        $this->service->refreshStatus($challenge);

        if ($challenge->status !== 'open' || $challenge->participants()->where('user_id', '!=', $challenge->challenger_id)->exists()) {
            return response()->json(['message' => 'Questa sfida non è più giocabile.'], 403);
        }

        // La chiave la genera il browser dell'ospite: cosi' una richiesta ripetuta
        // (doppio tocco, rete lenta) riprende la stessa partita invece di bloccarla.
        $key = (string) $request->validate(['key' => ['required', 'string', 'min:20', 'max:40', 'alpha_num']])['key'];
        $attempt = $this->attempt($challenge, $key);

        if (! $attempt) {
            // Una sola partita da ospite per sfida.
            if (ChallengeGuestAttempt::where('challenge_id', $challenge->id)->exists()) {
                return response()->json(['message' => 'Qualcun altro ha già accettato questa sfida.'], 403);
            }

            try {
                $attempt = ChallengeGuestAttempt::create([
                    'challenge_id' => $challenge->id,
                    'guest_key' => $key,
                    'started_at' => now(),
                    'answers' => [],
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $attempt = $this->attempt($challenge, $key);
                if (! $attempt) {
                    return response()->json(['message' => 'Qualcun altro ha già accettato questa sfida.'], 403);
                }
            }
        }

        if ($attempt->finished_at) {
            return response()->json(['message' => 'Hai già giocato questa sfida.'], 403);
        }

        $questions = $challenge->questions()->with(['answers', 'quiz.trainingCategory', 'quiz.trainingSubcategory'])->get();

        return response()->json([
            'key' => $attempt->guest_key,
            'total_questions' => $questions->count(),
            'answered_question_ids' => collect($attempt->answers)->pluck('question_id')->all(),
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
                'training' => $question->quiz?->title,
                'answers' => $question->answers->shuffle()->map(fn ($answer) => [
                    'id' => $answer->id,
                    'answer_text' => $answer->answer_text,
                ])->values(),
            ])->values(),
        ]);
    }

    /**
     * Registra una risposta (stesso punteggio delle sfide normali). Non dice
     * se era giusta: le soluzioni si vedono solo dopo la registrazione.
     */
    public function answer(Request $request, string $token)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:40'],
            'question_id' => ['required', 'integer'],
            'answer_id' => ['nullable', 'integer'],
            'time_taken' => ['required', 'integer', 'min:0'],
        ]);

        $challenge = $this->challenge($token);
        $attempt = $this->attempt($challenge, $data['key']);

        if (! $attempt || $attempt->finished_at || $challenge->status !== 'open') {
            return response()->json(['message' => 'Tentativo non valido'], 403);
        }

        $question = $challenge->questions()->where('questions.id', $data['question_id'])->first();
        if (! $question) {
            return response()->json(['message' => 'Domanda non valida per questa sfida'], 422);
        }

        $answers = collect($attempt->answers);
        if ($answers->contains('question_id', $question->id)) {
            return response()->json(['message' => 'Hai già risposto a questa domanda'], 403);
        }

        $answerId = $data['answer_id'] ?? null;
        if ($answerId !== null && ! $question->answers()->whereKey($answerId)->exists()) {
            return response()->json(['message' => 'Risposta non valida'], 422);
        }

        $last = $answers->max('at');
        $scored = $this->service->scoreAnswer(
            $question,
            $answerId,
            (int) $data['time_taken'],
            $last ? Carbon::parse($last) : $attempt->started_at,
            (int) $answers->sum('score')
        );

        $answers->push(['question_id' => $question->id, ...$scored, 'at' => now()->toISOString()]);
        $attempt->update(['answers' => $answers->values()->all()]);

        return response()->json(['recorded' => true, 'answered_count' => $answers->count()]);
    }

    public function finish(Request $request, string $token)
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:40']]);

        $challenge = $this->challenge($token);
        $attempt = $this->attempt($challenge, $data['key']);

        if (! $attempt || $attempt->finished_at) {
            return response()->json(['message' => 'Tentativo non valido'], 403);
        }

        // Domande non risposte (es. pagina chiusa): contano come tempo scaduto.
        $answers = collect($attempt->answers);
        foreach ($challenge->questions()->get() as $question) {
            if ($answers->contains('question_id', $question->id)) {
                continue;
            }
            $answers->push([
                'question_id' => $question->id,
                ...$this->service->scoreAnswer($question, null, (int) $question->time_limit_seconds * 1000, null, (int) $answers->sum('score')),
                'at' => now()->toISOString(),
            ]);
        }

        $attempt->update([
            'answers' => $answers->values()->all(),
            'score' => (int) $answers->sum('score'),
            'total_time' => (int) $answers->sum('time_taken'),
            'correct_answers' => $answers->where('is_correct', true)->count(),
            'finished_at' => now(),
        ]);

        return response()->json($this->result($challenge, $attempt->fresh()));
    }

    /**
     * Cosa vede l'ospite a fine partita: solo il suo punteggio. Chi ha vinto
     * e le risposte giuste si vedono dopo la registrazione.
     */
    private function result(Challenge $challenge, ChallengeGuestAttempt $attempt): array
    {
        return [
            'score' => (int) $attempt->score,
            'correct_answers' => (int) $attempt->correct_answers,
            'total_questions' => Challenge::QUESTIONS_COUNT,
            'challenger' => $challenge->challenger->nickname,
            'challenger_played' => $challenge->attempts()->where('user_id', $challenge->challenger_id)->where('completed', true)->exists(),
            'key' => $attempt->guest_key,
            'ref' => $this->referrals->code($challenge->challenger),
            'welcome_coins' => (int) config('referral.welcome_coins'),
        ];
    }

    /**
     * Chi ha creato la sfida la manda via email all'amico (stessi limiti degli inviti).
     */
    public function email(Request $request, Challenge $challenge)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $user = $request->user();

        abort_unless($challenge->challenger_id === $user->id && $challenge->guest_token, 404);

        $email = strtolower(trim($data['email']));
        if (strcasecmp($email, $user->email) === 0) {
            return response()->json(['message' => 'Non puoi sfidare te stesso 😄'], 422);
        }
        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Questa persona è già su Midalot: sfidala cercandola tra i membri!'], 422);
        }

        $today = DB::table('invite_emails')->where('user_id', $user->id)->where('created_at', '>=', now()->subDay())->count();
        if ($today >= (int) config('referral.emails_per_day', 5)) {
            return response()->json(['message' => 'Hai già mandato '.$today.' inviti oggi. Condividi il link su WhatsApp!'], 429);
        }

        Mail::to($email)->send(new GuestChallengeMail($challenge, $user, $this->url($challenge)));

        $challenge->update(['guest_invited_at' => now()]);

        DB::table('invite_emails')->insert([
            'user_id' => $user->id,
            'email_hash' => hash('sha256', $email),
            'kind' => 'challenge',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Sfida inviata! Il tuo amico riceverà un\'email con il link.', 'invited' => true]);
    }

    public static function url(Challenge $challenge): string
    {
        return rtrim(config('app.frontend_url'), '/').'/sfida-amico/'.$challenge->guest_token;
    }
}
