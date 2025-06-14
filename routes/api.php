<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\TopUpController;
use App\Http\Controllers\WalletController;
use App\Models\TopUp;
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
Route::get('/betReady', [BetController::class, 'betSignal'])->name('betReady');
Route::post('/bets', [BetController::class, 'storeMultipleBets'])->name('bets.store');
Route::get('/showBetsByResultId/{result_id}', [BetController::class, 'showBetsByResultId'])->name('bets.showByResultId');
Route::get('/showBetsByUserID&ResultId/{result_id?}', [BetController::class, 'showBetByUserIDandResultID'])->name('bets.showBetByUserIDandResultID');
Route::get('/bet-limit-exceeded/{result_id}/{number}', [BetController::class, 'isBetLimitExceeded'])->name('bets.isBetLimitExceeded');

//Wallet Routes
// Route::apiResource('bets', WalletController::class);
Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
Route::get('/wallets/{user_id}', [WalletController::class, 'show'])->name('wallets.show');
Route::get('/wallets/source/filter', [WalletController::class, 'walletSource'])->name('wallets.source.filter');


// Withdraw
Route::get('/wallets/withdrawable/{user_id}', [WalletController::class, 'withdrawableSources']);

// TopUp
// Route::post('/wallets/topup', [WalletController::class, 'topup']);
Route::get('/wallets/topup/all', [TopUpController::class, 'index']);
Route::post('/wallets/topup', [TopUpController::class, 'store']);
Route::put('/wallets/topup/confirm/{topup_id}', [TopUpController::class, 'confirmTopUpFlagByTopupId']);
Route::get('/wallets/topup/{user_id}', [TopUpController::class, 'showTopUpWallets']);

// Result
Route::get('/results', [ResultController::class, 'index'])->name('results.index');
Route::get('/results/pagination', [ResultController::class, 'indexPagination'])->name('results.indexPagination');
Route::get('/resultSignal', [ResultController::class, 'resultSignal'])->name('resultSignal');
Route::get('/results/id/{result_id?}', [ResultController::class, 'showRecentOrByRID'])->name('results.showRecentOrByRID');
Route::get('/results/userid/{user_id}/{result_id?}', [ResultController::class, 'showRecentOrByRIDandUID'])->name('results.showRecentOrByRIDandUID');
Route::get('/results/all/{user_id}', [ResultController::class, 'showByUID'])->name('results.showByUID');
Route::get('/results/all/pagination/{user_id}', [ResultController::class, 'showByUIDPagination'])->name('results.showByUIDPagination');
Route::get('/results/date/{date?}', [ResultController::class, 'showByDate'])->name('results.showByDate');
Route::delete('/results/id/{result_id}', [ResultController::class, 'deleteById'])->name('results.deleteById');
Route::delete('/results/date/{date}', [ResultController::class, 'deleteByDate'])->name('results.deleteByDate');

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
