<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ITunesController;
use App\Http\Controllers\Admin\MidalarioController;
use App\Http\Controllers\Admin\MinigiocoController;
use App\Http\Controllers\Admin\MinigiocoRoundController;
use App\Http\Controllers\Admin\PeriodLeaderboardController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\ShowcaseImageController;
use App\Http\Controllers\Admin\TrainingCategoryController;
use App\Http\Controllers\Admin\TrainingQuestionReportController;
use App\Http\Controllers\Admin\TrainingSubcategoryController;
use App\Http\Controllers\Admin\TrainingQuizController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if (Auth::check() && Auth::user()->is_admin) {
        return redirect()->route('admin.index');
    }

    if (Auth::check()) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.index');
})->middleware(['auth', 'verified', 'admin'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])
    ->name('admin.')
    ->prefix('admin')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])
            ->name('index');

        Route::get('users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])
            ->name('users.show');

        Route::get('training/categories', [TrainingCategoryController::class, 'index'])
            ->name('training.categories.index');
        Route::post('training/categories', [TrainingCategoryController::class, 'store'])
            ->name('training.categories.store');
        Route::put('training/categories/{category}', [TrainingCategoryController::class, 'update'])
            ->name('training.categories.update');
        Route::delete('training/categories/{category}', [TrainingCategoryController::class, 'destroy'])
            ->name('training.categories.destroy');

        Route::post('training/categories/{category}/subcategories', [TrainingSubcategoryController::class, 'store'])
            ->name('training.subcategories.store');
        Route::put('training/subcategories/{subcategory}', [TrainingSubcategoryController::class, 'update'])
            ->name('training.subcategories.update');
        Route::delete('training/subcategories/{subcategory}', [TrainingSubcategoryController::class, 'destroy'])
            ->name('training.subcategories.destroy');

        Route::resource('training/quizzes', TrainingQuizController::class)
            ->names('training.quizzes')
            ->parameters(['quizzes' => 'quiz']);

        Route::get('training/quizzes/{quiz}/leaderboard', [TrainingQuizController::class, 'leaderboard'])
            ->name('training.quizzes.leaderboard');
        Route::patch('training/quizzes/{quiz}/toggle-leaderboard', [TrainingQuizController::class, 'toggleLeaderboard'])
            ->name('training.quizzes.toggleLeaderboard');

        Route::get('midalario', [MidalarioController::class, 'index'])
            ->name('midalario.index');
        Route::get('midalario/create', [MidalarioController::class, 'create'])
            ->name('midalario.create');
        Route::post('midalario', [MidalarioController::class, 'store'])
            ->name('midalario.store');
        Route::get('midalario/{quiz}/edit', [MidalarioController::class, 'edit'])
            ->name('midalario.edit');
        Route::put('midalario/{quiz}', [MidalarioController::class, 'update'])
            ->name('midalario.update');
        Route::delete('midalario/{quiz}', [MidalarioController::class, 'destroy'])
            ->name('midalario.destroy');
        Route::get('midalario/{quiz}/monitor', [MidalarioController::class, 'monitor'])
            ->name('midalario.monitor');
        Route::patch('midalario/{quiz}/close', [MidalarioController::class, 'closeParticipation'])
            ->name('midalario.close');
        Route::patch('midalario/{quiz}/reopen', [MidalarioController::class, 'reopenParticipation'])
            ->name('midalario.reopen');
        Route::patch('midalario/{quiz}/start', [MidalarioController::class, 'start'])
            ->name('midalario.start');

        Route::get('itunes/search', [ITunesController::class, 'search'])
            ->name('itunes.search');

        Route::get('showcase', [ShowcaseImageController::class, 'index'])
            ->name('showcase.index');
        Route::post('showcase', [ShowcaseImageController::class, 'store'])
            ->name('showcase.store');
        Route::delete('showcase/{showcase}', [ShowcaseImageController::class, 'destroy'])
            ->name('showcase.destroy');

        Route::get('reports', [TrainingQuestionReportController::class, 'index'])
            ->name('reports.index');
        Route::patch('reports/{report}', [TrainingQuestionReportController::class, 'update'])
            ->name('reports.update');
        Route::delete('reports/{report}', [TrainingQuestionReportController::class, 'destroy'])
            ->name('reports.destroy');

        // CRUD Quiz
        Route::resource('quizzes', QuizController::class);

        // CRUD Domande
        Route::resource('quizzes.questions', QuestionController::class);

        /*
        |--------------------------------------------------------------------------
        | GESTIONE UTENTI QUIZ
        |--------------------------------------------------------------------------
        */

        // Pagina gestione utenti del quiz
        Route::get(
            'quizzes/{quiz}/users',
            [QuizController::class, 'manageUsers']
        )->name('quizzes.users');

        // Ricerca utenti (AJAX) legata al quiz
        Route::get(
            'quizzes/{quiz}/users/search',
            [QuizController::class, 'searchUsers']
        )->name('quizzes.users.search');

        // Associa utenti al quiz
        Route::post(
            'quizzes/{quiz}/attach-users',
            [QuizController::class, 'attachUsers']
        )->name('quizzes.attachUsers');

        // Classifica utenti
        Route::get('quizzes/{quiz}/leaderboard', [QuizController::class, 'leaderboard'])
            ->name('quizzes.leaderboard');

        Route::patch('quizzes/{quiz}/toggle-leaderboard', [QuizController::class, 'toggleLeaderboard'])
            ->name('quizzes.toggleLeaderboard');

        // Rimuovi utente dal quiz
        Route::delete(
            'quizzes/{quiz}/users/{user}',
            [QuizController::class, 'detachUser']
        )->name('quizzes.detachUser');

        /*
        |--------------------------------------------------------------------------
        | MINIGIOCHI
        |--------------------------------------------------------------------------
        */

        // CRUD Minigiochi
        Route::resource('minigiochi', MinigiocoController::class)
            ->parameters(['minigiochi' => 'minigioco']);

        // CRUD Domande (round) dei minigiochi
        Route::resource('minigiochi.rounds', MinigiocoRoundController::class)
            ->parameters(['minigiochi' => 'minigioco', 'rounds' => 'round']);

        Route::get('minigiochi/{minigioco}/leaderboard', [MinigiocoController::class, 'leaderboard'])
            ->name('minigiochi.leaderboard');

        Route::patch('minigiochi/{minigioco}/toggle-leaderboard', [MinigiocoController::class, 'toggleLeaderboard'])
            ->name('minigiochi.toggleLeaderboard');

        Route::get('classifica-premi', [PeriodLeaderboardController::class, 'index'])
            ->name('period-leaderboard.index');
    });

Route::get('/cities/search', [CityController::class, 'search'])
    ->middleware('throttle:60,1')
    ->name('cities.search');

require __DIR__.'/auth.php';
