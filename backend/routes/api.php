<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuizPlayController;
use App\Http\Controllers\Api\UserQuizController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/my-quizzes', [UserQuizController::class, 'index']);
    Route::get('/quizzes/{quiz}', [UserQuizController::class, 'show']);

    Route::post('/quiz/{quiz}/start', [QuizPlayController::class, 'start']);
    Route::post('/quiz/answer', [QuizPlayController::class, 'submitAnswer']);
    Route::post('/quiz/finish', [QuizPlayController::class, 'finishQuiz']);
});
