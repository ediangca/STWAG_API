<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

Route::get('/custom_user_mail/{user_id}', [AuthController::class, 'customUserMail'])->name('customEmail');