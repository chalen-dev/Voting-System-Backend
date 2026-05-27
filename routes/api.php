<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\PersonalAccessTokenController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\VoteController;
use App\Http\Middleware\AuthTokenMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/polls/{poll}', [PollController::class, 'show']);
Route::post('/votes', [VoteController::class, 'store']);
Route::get('/votes', [VoteController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Token Auth)
|--------------------------------------------------------------------------
*/

Route::middleware(AuthTokenMiddleware::class)->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Polls
    Route::get('/polls', [PollController::class, 'index']);
    Route::post('/polls', [PollController::class, 'store']);
    Route::put('/polls/{poll}', [PollController::class, 'update']);
    Route::delete('/polls/{poll}', [PollController::class, 'destroy']);

    // Options
    Route::get('/options', [OptionController::class, 'index']);
    Route::post('/options', [OptionController::class, 'store']);
    Route::get('/options/{option}', [OptionController::class, 'show']);
    Route::put('/options/{option}', [OptionController::class, 'update']);
    Route::delete('/options/{option}', [OptionController::class, 'destroy']);

    // Tokens
    Route::get('/tokens', [PersonalAccessTokenController::class, 'index']);
    Route::delete('/tokens/{personalAccessToken}', [PersonalAccessTokenController::class, 'destroy']);
});
