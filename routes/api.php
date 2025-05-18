<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\LotteryController;

Route::get('/', function () {
    return response()->json([
        'ApiName' => 'STWAG',
        'laravel_version' => app()->version(), // Get the Laravel version
        'message' => 'This API is under development. Please check back soon.',
        'By' => 'Mr. Ebrahim Diangca and John Louis Mercaral, MIS',
    ]);
});


Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth', [AuthController::class, 'login']);


// Route::put('/users/avatar/{id}', [AuthController::class, 'updateAvatarById']);

Route::get('/users', [AuthController::class, 'index']);
// Route::get('/users/{id}', [AuthController::class, 'getUserById']);

// Route::get('/users/{id}/downline', [AuthController::class, 'getDownlines']);
// Route::get('/users/{id}/upline', [AuthController::class, 'getUpline']);


// Route::get('/users/type/{type}', [AuthController::class, 'getUserByType']);

// Lottery Routes
Route::get('/lottery', [LotteryController::class, 'index']);
Route::post('/lottery', [LotteryController::class, 'store']);

Route::get('/stwag', [BetController::class, 'spinResult']);

// Authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::controller(AuthController::class)->group(function () {
        // Route::post('/logout', [AuthController::class, 'logout']);
        // Route::post('/refresh', [AuthController::class, 'r efresh']);

        // Route::get('/users', 'index');
        // Route::get('/users/{id}', 'getUserById');
    });
});



Route::controller(AuthController::class)->group(function () {

    
    // Route::get('/users', 'index');

//     Route::post('/register', 'register');
//     Route::post('/login', 'login');
//     Route::get('/users', 'getAllUsers');
//     Route::get('/users/{id}', 'getUserById');

//     Route::get('/users/{id}/downline/', 'getDownlines');
//     Route::get('/users/{id}/upline/', 'getUpline');
    
});


Route::apiResource('bets', BetController::class);