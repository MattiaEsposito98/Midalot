<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\TrainingCategory;
use App\Models\TrainingSubcategory;
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
            'training_categories' => TrainingCategory::count(),
            'training_subcategories' => TrainingSubcategory::count(),
            'midalarios' => Quiz::where('type', 'midalario')->count(),
            'midalarios_live' => Quiz::where('type', 'midalario')
                ->whereIn('midalario_status', ['open', 'closed', 'running'])
                ->count(),
            'midalario_participants' => QuizParticipant::distinct('user_id')->count('user_id'),
            'minigiochi' => Minigioco::count(),
            'active_minigiochi' => Minigioco::where('is_active', true)->count(),
            'minigioco_attempts' => MinigiocoAttempt::count(),
            'minigioco_completed_attempts' => MinigiocoAttempt::where('completed', true)->count(),
            'minigioco_players' => MinigiocoAttempt::distinct('user_id')->count('user_id'),
            'questions' => Question::count(),
            'logins_today' => UserLogin::whereHas('user', fn ($q) => $q->where('is_admin', false))
                ->whereDate('logged_in_at', today())
                ->count(),
            'logins_week' => UserLogin::whereHas('user', fn ($q) => $q->where('is_admin', false))
                ->where('logged_in_at', '>=', now()->subDays(7))
                ->count(),
            'attempts' => QuizAttempt::count(),
            'completed_attempts' => QuizAttempt::where('completed', true)->count(),
            'cities' => User::where('is_admin', false)
                ->whereNotNull('city_id')
                ->distinct('city_id')
                ->count('city_id'),
        ];

        $latestLogins = UserLogin::with('user.latestMonthlyBadge')
            ->whereHas('user', fn ($q) => $q->where('is_admin', false))
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

        $recentQuizzes = Quiz::where('type', 'assigned')
            ->withCount(['questions', 'users', 'attempts'])
            ->latest()
            ->limit(5)
            ->get();

        $recentTrainings = Quiz::where('type', 'training')
            ->withCount(['questions', 'attempts'])
            ->latest()
            ->limit(5)
            ->get();

        $recentMidalarios = Quiz::where('type', 'midalario')
            ->withCount(['questions', 'participants', 'attempts'])
            ->latest()
            ->limit(5)
            ->get();

        $recentMinigiochi = Minigioco::withCount(['rounds', 'attempts'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'latestLogins',
            'topCities',
            'recentQuizzes',
            'recentTrainings',
            'recentMidalarios',
            'recentMinigiochi'
        ));
    }
}
