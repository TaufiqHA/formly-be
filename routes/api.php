<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FormController;
use App\Http\Controllers\Api\V1\FormFieldController;
use App\Http\Controllers\Api\V1\PublicFormController;
use App\Http\Controllers\IntegrationController;
use Illuminate\Support\Facades\Route;

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

        // Form Fields API
        Route::put('forms/{id}/fields', [FormFieldController::class, 'updateBulk']);
    });

    // Public Routes (Tanpa Auth)
    Route::prefix('public/forms')->group(function () {
        Route::get('/{slug}', [PublicFormController::class, 'show']);
        Route::post('/{slug}/submit', [PublicFormController::class, 'submit']);
    });
});
Route::get('/v1/test-guzzle', [IntegrationController::class, 'getData']);
