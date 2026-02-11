<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceSessionController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\MagicLinkController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PersonalAccessTokenController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Routes for API version 1. All routes are prefixed with /api/v1
| and use the 'api' middleware group + Sanctum auth where needed.
|
*/

// Public auth routes
Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    Route::post('/magic-link', [MagicLinkController::class, 'request']);
    Route::post('/magic-link/verify', [MagicLinkController::class, 'verify']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function (): void {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Email verification
    Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend']);
    Route::post('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed']);

    // Two-factor authentication
    Route::prefix('auth/two-factor')->group(function (): void {
        Route::post('/enable', [TwoFactorController::class, 'enable']);
        Route::post('/confirm', [TwoFactorController::class, 'confirm']);
        Route::delete('/', [TwoFactorController::class, 'disable']);
        Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    });

    // Account management
    Route::prefix('account')->group(function (): void {
        Route::put('/password', [AuthController::class, 'changePassword']);

        // Device sessions
        Route::get('/sessions', [DeviceSessionController::class, 'index']);
        Route::delete('/sessions/{session}', [DeviceSessionController::class, 'destroy']);
        Route::delete('/sessions', [DeviceSessionController::class, 'destroyAll']);

        // Personal access tokens
        Route::get('/tokens', [PersonalAccessTokenController::class, 'index']);
        Route::post('/tokens', [PersonalAccessTokenController::class, 'store']);
        Route::delete('/tokens/{token}', [PersonalAccessTokenController::class, 'destroy']);
    });
});
