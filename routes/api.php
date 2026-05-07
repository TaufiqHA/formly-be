<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FormController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IntegrationController;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        // Public route
        Route::post('/login', [AuthController::class, 'login']);

        // Protected routes (Butuh Token)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // Forms API
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('forms', FormController::class);
        Route::patch('forms/{id}/status', [FormController::class, 'updateStatus']);
        Route::get('forms/{id}/stats', [FormController::class, 'stats']);
    });
});
Route::get('/v1/test-guzzle', [IntegrationController::class, 'getData']);
