<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;





Route::prefix('auth')->group(function () {
    require('routers/auth.php');
});
Route::prefix('app')->group(function () {
    require('routers/app.php');
});
require('routers/settings.php');
