<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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

        // Rimuovi utente dal quiz
        Route::delete(
            'quizzes/{quiz}/users/{user}',
            [QuizController::class, 'detachUser']
        )->name('quizzes.detachUser');
    });

require __DIR__ . '/auth.php';
