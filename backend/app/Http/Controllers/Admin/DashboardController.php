<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::where('is_admin', false)->count(),
            'quizzes' => Quiz::where('type', 'assigned')->count(),
            'active_quizzes' => Quiz::where('type', 'assigned')->where('is_active', true)->count(),
            'trainings' => Quiz::where('type', 'training')->count(),
            'active_trainings' => Quiz::where('type', 'training')->where('is_active', true)->count(),
            'questions' => Question::count(),
            'logins_today' => UserLogin::whereDate('logged_in_at', today())->count(),
            'logins_week' => UserLogin::where('logged_in_at', '>=', now()->subDays(7))->count(),
            'attempts' => QuizAttempt::count(),
            'completed_attempts' => QuizAttempt::where('completed', true)->count(),
            'cities' => User::where('is_admin', false)
                ->whereNotNull('city_id')
                ->distinct('city_id')
                ->count('city_id'),
        ];

        $latestLogins = UserLogin::with('user')
            ->latest('logged_in_at')
            ->limit(6)
            ->get();

        $topCities = DB::table('users')
            ->join('cities', 'users.city_id', '=', 'cities.id')
            ->where('users.is_admin', false)
            ->select('cities.name', DB::raw('COUNT(users.id) as users_count'))
            ->groupBy('cities.id', 'cities.name')
            ->orderByDesc('users_count')
            ->limit(6)
            ->get();

        $recentQuizzes = Quiz::withCount(['questions', 'users', 'attempts'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'latestLogins', 'topCities', 'recentQuizzes'));
    }
}
