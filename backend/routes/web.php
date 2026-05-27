<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
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

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])
    ->name("admin.")
    ->prefix("admin")
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])
            ->name('index');

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
    });

Route::get('/cities/search', [CityController::class, 'search'])
    ->name('cities.search');

require __DIR__ . '/auth.php';
