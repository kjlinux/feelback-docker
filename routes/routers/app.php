<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TestDataController;
use App\Http\Controllers\Api\DashboardController;

Route::prefix('test-data')->group(function () {

    Route::post('/generate', [TestDataController::class, 'generateTestData']);


    Route::post('/feedback/manual', [TestDataController::class, 'addManualFeedback']);


    Route::get('/devices', [TestDataController::class, 'getDevicesForTesting']);


    Route::get('/examples', [TestDataController::class, 'getConfigurationExamples']);


    Route::delete('/clean', [TestDataController::class, 'cleanTestData']);
});

Route::prefix('dashboard')->middleware(['auth:api'])->group(function () {


    Route::get('/global-stats', [DashboardController::class, 'getGlobalStatistics']);


    Route::get('/trends', [DashboardController::class, 'getTemporalTrends']);


    Route::get('/devices', [DashboardController::class, 'getDevicePerformance']);


    Route::get('/hourly-patterns', [DashboardController::class, 'getHourlyPatterns']);


    Route::get('/sentiment-distribution', [DashboardController::class, 'getSentimentDistribution']);


    Route::get('/alerts', [DashboardController::class, 'getAlerts']);


    Route::get('/complete', [DashboardController::class, 'getDashboardData']);
});
