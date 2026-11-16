<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\AllowedNickname;
use App\Support\NicknameFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Colonne ordinabili dall'header della tabella: la chiave e' il valore
     * accettato in ?sort=, il valore la colonna SQL reale su cui ordinare
     * (city usa la colonna della tabella joinata, non della relazione).
     */
    private const SORTABLE_COLUMNS = [
        'name' => 'users.name',
        'nickname' => 'users.nickname',
        'email' => 'users.email',
        'city' => 'cities.name',
        'birth_date' => 'users.birth_date',
        'created_at' => 'users.created_at',
        'email_verified_at' => 'users.email_verified_at',
    ];

    public function index(Request $request)
    {
        $search = $request->get('search');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        if (! array_key_exists($sort, self::SORTABLE_COLUMNS)) {
            $sort = 'created_at';
        }

        $users = User::query()
            ->select('users.*')
            ->leftJoin('cities', 'cities.id', '=', 'users.city_id')
            ->where('users.is_admin', false)
            ->with(['city', 'latestMonthlyBadge'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.nickname', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->orderBy(self::SORTABLE_COLUMNS[$sort], $direction)
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'sort', 'direction'));
    }

    public function show(User $user)
    {
        abort_if($user->is_admin, 404);

        $user->load(['city', 'latestMonthlyBadge']);

        $quizAttempts = $user->quizAttempts()
            ->with('quiz')
            ->latest('started_at')
            ->get();

        $trainingAttempts = $user->trainingAttempts()
            ->with(['quiz', 'category'])
            ->latest('started_at')
            ->get();

        $logins = $user->logins()
            ->latest('logged_in_at')
            ->limit(20)
            ->get();

        $stats = [
            'assigned_quizzes' => $user->quizzes()->count(),
            'quiz_attempts' => $quizAttempts->count(),
            'quiz_completed' => $quizAttempts->where('completed', true)->count(),
            'quiz_avg_score' => round($quizAttempts->where('completed', true)->avg('score'), 1),
            'training_attempts' => $trainingAttempts->count(),
            'training_completed' => $trainingAttempts->where('completed', true)->count(),
            'training_avg_score' => round($trainingAttempts->where('completed', true)->avg('score'), 1),
            'logins_count' => $user->logins()->count(),
            'last_login' => $user->logins()->latest('logged_in_at')->first()?->logged_in_at,
        ];

        // Nickname con parolacce o bestemmie: avviso e proposta di sostituzione.
        $nicknameProblem = NicknameFilter::reason($user->nickname);
        $suggestedNickname = self::suggestNickname($user);

        return view('admin.users.show', compact('user', 'quizAttempts', 'trainingAttempts', 'logins', 'stats', 'nicknameProblem', 'suggestedNickname'));
    }

    /**
     * L'admin cambia il nickname di un utente (es. uno offensivo). Stesse regole della
     * registrazione. Chi entrava con il vecchio nickname ora entra con il nuovo o con l'email.
     */
    public function updateNickname(Request $request, User $user)
    {
        abort_if($user->is_admin, 404);

        $request->merge(['nickname' => strtolower(trim((string) $request->input('nickname')))]);
        $data = $request->validate([
            'nickname' => [
                'required', 'string', 'min:3', 'max:30',
                Rule::unique('users', 'nickname')->ignore($user->id),
                'regex:/^(?!.*\.\.)(?!.*\.$)(?!^\.)[a-z0-9._]+$/',
                new AllowedNickname(),
            ],
        ], [
            'nickname.regex' => 'Solo lettere minuscole, numeri, punto e trattino basso (niente punti doppi o finali).',
            'nickname.unique' => 'Questo nickname è già usato da un altro utente.',
        ]);

        $old = $user->nickname;
        $user->forceFill(['nickname' => $data['nickname']])->save();

        Log::info('Nickname cambiato dall\'admin', [
            'user_id' => $user->id,
            'da' => $old,
            'a' => $data['nickname'],
            'admin_id' => $request->user()->id,
        ]);

        return back()->with('success', "Nickname cambiato da {$old} a {$data['nickname']}.");
    }

    /**
     * Proposta di nickname ricavata dall'email (la parte prima della @), valida e libera;
     * se è già presa si aggiunge un numero.
     */
    public static function suggestNickname(User $user): string
    {
        $base = strtolower((string) strstr((string) $user->email, '@', true));
        $base = preg_replace('/[^a-z0-9._]+/', '.', $base);
        $base = preg_replace('/\.{2,}/', '.', $base);
        $base = substr(trim($base, '.'), 0, 26);
        $base = rtrim($base, '.');

        if (strlen($base) < 3 || ! NicknameFilter::isAllowed($base)) {
            $base = 'utente';
        }

        $candidate = $base;
        for ($i = 2; User::where('nickname', $candidate)->where('id', '!=', $user->id)->exists(); $i++) {
            $candidate = $base.$i;
        }

        return $candidate;
    }
}
