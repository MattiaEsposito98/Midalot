<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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

        return view('admin.users.show', compact('user', 'quizAttempts', 'trainingAttempts', 'logins', 'stats'));
    }
}
