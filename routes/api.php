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
| These endpoints do not require an Authentication Token.
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Allows anyone to view a poll's details/options
Route::get('/polls/{poll}', [PollController::class, 'show']);

// Allows anyone to cast a vote (Handled via IP hashing in Controller)
Route::post('/votes', [VoteController::class, 'store']);

// Typically used to show live results to the public
Route::get('/votes', [VoteController::class, 'index']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Token Auth)
|--------------------------------------------------------------------------
| These endpoints require a valid 'Authorization: Bearer <token>' header.
*/

Route::middleware(AuthTokenMiddleware::class)->group(function () {
    // Auth Management
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Poll Management (CRUD)
    Route::get('/polls', [PollController::class, 'index']);
    Route::post('/polls', [PollController::class, 'store']);

    // Bulk routes MUST be before {poll} wildcard
    Route::post('/polls/bulk-status', [PollController::class, 'bulkStatus']);
    Route::delete('/polls/bulk-destroy', [PollController::class, 'bulkDestroy']);

    // Single poll routes AFTER
    Route::put('/polls/{poll}', [PollController::class, 'update']);
    Route::delete('/polls/{poll}', [PollController::class, 'destroy']);

    // Option Management
    Route::get('/options', [OptionController::class, 'index']);
    Route::post('/options', [OptionController::class, 'store']);
    Route::get('/options/{option}', [OptionController::class, 'show']);
    Route::put('/options/{option}', [OptionController::class, 'update']);
    Route::delete('/options/{option}', [OptionController::class, 'destroy']);

    // Token/Session Management
    Route::get('/tokens', [PersonalAccessTokenController::class, 'index']);
    Route::delete('/tokens/{personalAccessToken}', [PersonalAccessTokenController::class, 'destroy']);
});
