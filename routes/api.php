<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'ApiName' => 'STWAG',
        'laravel_version' => app()->version(), // Get the Laravel version
    ]);
});


Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});