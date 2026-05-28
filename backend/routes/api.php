<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuizPlayController;
use App\Http\Controllers\Api\UserQuizController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\TrainingController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

Route::get('/training/categories', [TrainingController::class, 'categories']);
Route::get('/training/categories/{categorySlug}/quizzes', [TrainingController::class, 'categoryQuizzes']);
Route::get('/training/quizzes/{quiz}', [TrainingController::class, 'show']);
Route::post('/training/quizzes/{quiz}/guest-start', [TrainingController::class, 'guestStart']);
Route::post('/training/guest-answer', [TrainingController::class, 'guestAnswer']);
Route::post('/training/guest-finish', [TrainingController::class, 'guestFinish']);


/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION (FIX PER API)
|--------------------------------------------------------------------------
*/

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {

    $user = User::findOrFail($id);

    // 🔐 verifica hash email
    if (!hash_equals((string) $hash, sha1($user->email))) {
        abort(403, 'Link non valido');
    }

    // ✅ verifica email (solo se non già verificata)
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect(config('app.frontend_url') . '/login?verified=1');
})->middleware(['signed'])->name('verification.verify');


/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (SANCTUM)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);

    // ✅ AGGIORNAMENTO PROFILO
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    Route::get('/my-quizzes', [UserQuizController::class, 'index']);
    Route::get('/quizzes/{quiz}', [UserQuizController::class, 'show']);

    Route::get('/quizzes/{quiz}/leaderboard', [UserQuizController::class, 'leaderboard']);

    Route::post('/quiz/{quiz}/start', [QuizPlayController::class, 'start']);
    Route::post('/quiz/answer', [QuizPlayController::class, 'submitAnswer']);
    Route::post('/quiz/finish', [QuizPlayController::class, 'finishQuiz']);

    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/training/quizzes/{quiz}/start', [TrainingController::class, 'start']);
    Route::post('/training/answer', [TrainingController::class, 'answer']);
    Route::post('/training/finish', [TrainingController::class, 'finish']);
    Route::get('/training/progress', [TrainingController::class, 'progress']);
    Route::get('/training/categories/{categorySlug}/leaderboard', [TrainingController::class, 'leaderboard']);
});
