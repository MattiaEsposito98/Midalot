<?php

use App\Http\Controllers\Api\AudioProxyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\MidalarioController;
use App\Http\Controllers\Api\PeriodLeaderboardController;
use App\Http\Controllers\Api\QuizPlayController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\ShowcaseController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\TrainingReportController;
use App\Http\Controllers\Api\UserQuizController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->middleware('throttle:3,1');

Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:5,1');

Route::get('/showcase', [ShowcaseController::class, 'index']);

Route::get('/audio-proxy', [AudioProxyController::class, 'stream'])
    ->name('audio.proxy');

Route::get('/midalario/announcement', [MidalarioController::class, 'announcement']);

Route::get('/leaderboard/weekly', [PeriodLeaderboardController::class, 'weekly']);
Route::get('/leaderboard/monthly', [PeriodLeaderboardController::class, 'monthly']);
Route::get('/leaderboard/weeks', [PeriodLeaderboardController::class, 'availableWeeks']);
Route::get('/leaderboard/months', [PeriodLeaderboardController::class, 'availableMonths']);

Route::get('/training/categories', [TrainingController::class, 'categories']);
Route::get('/training/categories/{categorySlug}/quizzes', [TrainingController::class, 'categoryQuizzes']);
Route::get('/training/quizzes/{quiz}', [TrainingController::class, 'show']);
Route::post('/training/quizzes/{quiz}/guest-start', [TrainingController::class, 'guestStart'])
    ->middleware('throttle:20,1');
Route::post('/training/guest-answer', [TrainingController::class, 'guestAnswer'])
    ->middleware('throttle:120,1');
Route::post('/training/guest-finish', [TrainingController::class, 'guestFinish'])
    ->middleware('throttle:60,1');
Route::post('/training/report-question', [TrainingReportController::class, 'store'])
    ->middleware('throttle:3,1');

/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION (FIX PER API)
|--------------------------------------------------------------------------
*/

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {

    $user = User::findOrFail($id);

    // 🔐 verifica hash email
    if (! hash_equals((string) $hash, sha1($user->email))) {
        abort(403, 'Link non valido');
    }

    // ✅ verifica email (solo se non già verificata)
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect(config('app.frontend_url').'/login?verified=1');
})->middleware(['signed'])->name('verification.verify');

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (SANCTUM)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/user', [AuthController::class, 'user']);

    // ✅ AGGIORNAMENTO PROFILO
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    Route::get('/my-quizzes', [UserQuizController::class, 'index']);
    Route::get('/quizzes/{quiz}', [UserQuizController::class, 'show']);

    Route::get('/quizzes/{quiz}/leaderboard', [UserQuizController::class, 'leaderboard']);
    Route::get('/quizzes/{quiz}/review', [UserQuizController::class, 'review']);

    Route::post('/quiz/{quiz}/start', [QuizPlayController::class, 'start']);
    Route::post('/quiz/answer', [QuizPlayController::class, 'submitAnswer']);
    Route::post('/quiz/finish', [QuizPlayController::class, 'finishQuiz']);

    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/training/quizzes/{quiz}/start', [TrainingController::class, 'start']);
    Route::post('/training/answer', [TrainingController::class, 'answer']);
    Route::post('/training/finish', [TrainingController::class, 'finish']);
    Route::get('/training/progress', [TrainingController::class, 'progress']);
    Route::get('/training/categories/{categorySlug}/leaderboard', [TrainingController::class, 'leaderboard']);

    Route::get('/midalario/quizzes', [MidalarioController::class, 'index']);
    Route::post('/midalario/quizzes/{quiz}/join', [MidalarioController::class, 'join']);
    Route::get('/midalario/quizzes/{quiz}/status', [MidalarioController::class, 'status']);
    Route::post('/midalario/quizzes/{quiz}/answer', [MidalarioController::class, 'answer']);
    Route::get('/midalario/quizzes/{quiz}/review', [MidalarioController::class, 'review']);
    Route::get('/midalario/quizzes/{quiz}/leaderboard', [MidalarioController::class, 'leaderboard']);
});
