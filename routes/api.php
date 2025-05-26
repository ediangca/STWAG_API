<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\WalletController;

use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return response()->json([
        'ApiName' => 'STWAG',
        'laravel_version' => app()->version(), // Get the Laravel version
        // 'environment' => config('app.env'), // Get the environment,
        // 'environment' => env('APP_ENV', 'production'), // Get the environment,
        'version' => '1.0.0',
        'timezone' => config('app.timezone'), // Get the timezone
        'timestamp' => now()->toDateTimeString(),
        'message' => 'This API is under development. Please check back soon.',
        'Develop By' => 'Mr. Ebrahim Diangca and John Louis Mercaral, MIS',
    ]);
});


Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});


Route::get('/mercaral', function () {
    return response()->json(['message' => 'pangit']);
});


Route::post('/register', [AuthController::class, 'register']);

Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');
Route::post('/auth', [AuthController::class, 'login']);


// Route::put('/users/avatar/{id}', [AuthController::class, 'updateAvatarById']);

Route::get('/users', [AuthController::class, 'index']);
Route::get('/users/{user_id}', [AuthController::class, 'userInfo']);
// Route::get('/users/{id}', [AuthController::class, 'getUserById']);

// Route::get('/users/{id}/downline', [AuthController::class, 'getDownlines']);
// Route::get('/users/{id}/upline', [AuthController::class, 'getUpline']);


// Route::get('/users/type/{type}', [AuthController::class, 'getUserByType']);

// Lottery Routes
// Route::apiResource('lottery_sessions', LotteryController::class);
Route::get('/lottery', [LotteryController::class, 'index']);
Route::post('/lottery', [LotteryController::class, 'store']);


// Bet Routes
// Route::apiResource('bets', BetController::class);
Route::get('/betReady', [BetController::class, 'betSignal']);
Route::post('/bets', [BetController::class, 'storeMultipleBets']);
Route::get('/showBetsByResultId/{resultId}', [BetController::class, 'showBetsByResultId']);
Route::get('/bet-limit-exceeded/{result_id}/{number}', [BetController::class, 'isBetLimitExceeded']);

//Wallet Routes
// Route::apiResource('bets', WalletController::class);
Route::get('/wallets', [WalletController::class, 'index']);
Route::get('/wallets/{user_id}', [WalletController::class, 'show']);
Route::get('/wallets/withdrawable/{user_id}', [WalletController::class, 'withdrawableSources']);

Route::get('/wallets', [WalletController::class, 'indexTopUp']);
Route::post('/wallets/topup', [WalletController::class, 'topup']);
Route::put('/wallets/topup/confirm/{topup_id}', [WalletController::class, 'updateTopUpConfirmFlagByTopupId']);
Route::get('/wallets/topup/{user_id}', [WalletController::class, 'showTopUpWallets']);

// Result
Route::get('/lottery/results/', [BetController::class, 'createResult']);

Route::get('/test-mail', function () {
    Mail::raw('This is a test email from STWAG using Gmail SMTP.', function ($message) {
        $message->to('ediangca22@gmail.com')
                ->subject('Test Email from STWAG');
    });

    return 'Test email sent!';
});


// Authentication
 
// Route::middleware(['auth:sanctum', 'verified'])->group(function () {
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

