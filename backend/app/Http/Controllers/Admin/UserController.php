<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $users = User::where('is_admin', false)
            ->with(['city', 'latestMonthlyBadge'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
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
