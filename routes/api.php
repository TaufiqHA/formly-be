<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FormController;
use App\Http\Controllers\Api\V1\FormFieldController;
use App\Http\Controllers\Api\V1\PublicFormController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SubmissionController;
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

    // Protected API
    Route::middleware('auth:sanctum')->group(function () {
        // Forms API
        Route::apiResource('forms', FormController::class);
        Route::patch('forms/{id}/status', [FormController::class, 'updateStatus']);
        Route::get('forms/{id}/stats', [FormController::class, 'stats']);

        // Form Fields API
        Route::put('forms/{id}/fields', [FormFieldController::class, 'updateBulk']);

        // Submissions
        Route::prefix('submissions')->group(function () {
            Route::get('/', [SubmissionController::class, 'index']);
            Route::get('/export', [SubmissionController::class, 'export']);
            Route::get('/{id}', [SubmissionController::class, 'show']);
            Route::patch('/{id}/status', [SubmissionController::class, 'updateStatus']);
            Route::post('/{id}/notes', [SubmissionController::class, 'addNote']);
            Route::post('/{id}/resend-wa', [SubmissionController::class, 'resendWa']);
        });

        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index']);
            Route::put('/', [SettingController::class, 'updatePreferences']);
            Route::put('/whatsapp', [SettingController::class, 'updateWhatsApp']);
            Route::post('/whatsapp/test', [SettingController::class, 'testWhatsApp']);
        });

        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('/summary', [AnalyticsController::class, 'summary']);
            Route::get('/trend', [AnalyticsController::class, 'trend']);
            Route::get('/status-distribution', [AnalyticsController::class, 'statusDistribution']);
        });
    });

    // Public Routes (Tanpa Auth)
    Route::prefix('public/forms')->group(function () {
        Route::get('/{slug}', [PublicFormController::class, 'show']);
        Route::post('/{slug}/submit', [PublicFormController::class, 'submit']);
    });
});
Route::get('/v1/test-guzzle', [IntegrationController::class, 'getData']);
