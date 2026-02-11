<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceSessionController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\MagicLinkController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PersonalAccessTokenController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\UserAccountController;
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

// Public invitation view (no auth required to view details)
Route::get('/invitations/{token}', [InvitationController::class, 'show']);

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
        Route::get('/export', [UserAccountController::class, 'export']);
        Route::delete('/', [UserAccountController::class, 'destroy']);
        Route::put('/email', [UserAccountController::class, 'changeEmail']);

        // Device sessions
        Route::get('/sessions', [DeviceSessionController::class, 'index']);
        Route::delete('/sessions/{session}', [DeviceSessionController::class, 'destroy']);
        Route::delete('/sessions', [DeviceSessionController::class, 'destroyAll']);

        // Personal access tokens
        Route::get('/tokens', [PersonalAccessTokenController::class, 'index']);
        Route::post('/tokens', [PersonalAccessTokenController::class, 'store']);
        Route::delete('/tokens/{token}', [PersonalAccessTokenController::class, 'destroy']);
    });

    // Tenants (no tenant header required — user sees their own tenants)
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
    Route::put('/tenants/{tenant}', [TenantController::class, 'update']);
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);

    // Tenant invitations
    Route::post('/tenants/{tenant}/invitations', [InvitationController::class, 'store']);
    Route::post('/tenants/{tenant}/invitations/{invitation}/resend', [InvitationController::class, 'resend']);
    Route::put('/tenants/{tenant}/invitations/{invitation}', [InvitationController::class, 'updateRole']);

    // Accept/decline invitations (authenticated, any user)
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->name('invitations.accept');
    Route::post('/invitations/{token}/decline', [InvitationController::class, 'decline']);

    // Notifications
    Route::prefix('notifications')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllRead']);
        Route::get('/preferences', [NotificationController::class, 'preferences']);
        Route::put('/preferences', [NotificationController::class, 'updatePreference']);
    });
});
