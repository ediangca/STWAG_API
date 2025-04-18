<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

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

// Authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users', [AuthController::class, 'getAllUsers']);