
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/custom_user_mail/{user_id}', [AuthController::class, 'customUserMail'])->name('customEmail');