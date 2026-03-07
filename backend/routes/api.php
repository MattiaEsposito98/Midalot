<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserQuizController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);

    // 🔥 QUIZ ASSEGNATI ALL'UTENTE
    Route::get('/my-quizzes', [UserQuizController::class, 'index']);
    Route::get('/quizzes/{quiz}', [UserQuizController::class, 'show']);
});
