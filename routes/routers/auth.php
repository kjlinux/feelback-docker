<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController;

Route::post('login', [UserController::class, 'login']);

Route::prefix('users')->middleware('auth:api')->group(function () {
    Route::get('profile/get', [UserController::class, 'profile']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('refresh', [UserController::class, 'refresh']);
});
