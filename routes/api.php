<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    require __DIR__ . '/routers/auth.php';
});
Route::middleware('auth:api')->group(function () {

    Route::prefix('app')->group(function () {
        require __DIR__ . '/routers/app.php';
    });

    require __DIR__ . '/routers/settings.php';
});
