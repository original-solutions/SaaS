<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminNoteController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\HealthCheckController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\SupportCaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Routes for the admin section. All routes are prefixed with /admin/api/v1
| and require Super Admin authentication.
|
*/

Route::middleware(['auth:sanctum', 'super-admin'])->group(function (): void {
    // Dashboard
    Route::get('/dashboard', AdminDashboardController::class);

    // Health check
    Route::get('/health', HealthCheckController::class);

    // Global search
    Route::get('/search', AdminSearchController::class);

    // Tenant management
    Route::prefix('tenants')->group(function (): void {
        Route::get('/', [AdminTenantController::class, 'index']);
        Route::get('/{tenant}', [AdminTenantController::class, 'show'])->withTrashed();
        Route::get('/{tenant}/members', [AdminTenantController::class, 'members']);
        Route::post('/{tenant}/disable', [AdminTenantController::class, 'disable']);
        Route::post('/{tenant}/enable', [AdminTenantController::class, 'enable']);
        Route::post('/{tenant}/force-logout', [AdminTenantController::class, 'forceLogout']);
        Route::get('/{tenant}/export', [AdminTenantController::class, 'export']);
        Route::delete('/{tenant}', [AdminTenantController::class, 'destroy']);
    });

    // User management
    Route::prefix('users')->group(function (): void {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{user}', [AdminUserController::class, 'show']);
        Route::post('/{user}/lock', [AdminUserController::class, 'lock']);
        Route::post('/{user}/unlock', [AdminUserController::class, 'unlock']);
        Route::post('/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
        Route::post('/{user}/revoke-sessions', [AdminUserController::class, 'revokeSessions']);
    });

    // Impersonation
    Route::middleware('throttle:admin-impersonate')->group(function (): void {
        Route::post('/impersonate/{user}/start', [ImpersonationController::class, 'start']);
        Route::post('/impersonate/{user}/stop', [ImpersonationController::class, 'stop']);
    });

    // Admin notes
    Route::prefix('notes')->group(function (): void {
        Route::get('/', [AdminNoteController::class, 'index']);
        Route::post('/', [AdminNoteController::class, 'store']);
        Route::put('/{adminNote}', [AdminNoteController::class, 'update']);
        Route::delete('/{adminNote}', [AdminNoteController::class, 'destroy']);
    });

    // Support cases
    Route::apiResource('support-cases', SupportCaseController::class);

    // Feature flags
    Route::prefix('feature-flags')->group(function (): void {
        Route::get('/', [FeatureFlagController::class, 'index']);
        Route::post('/', [FeatureFlagController::class, 'store']);
        Route::put('/{featureFlag}', [FeatureFlagController::class, 'update']);
        Route::delete('/{featureFlag}', [FeatureFlagController::class, 'destroy']);
        Route::post('/{featureFlag}/overrides', [FeatureFlagController::class, 'storeOverride']);
        Route::delete('/{featureFlag}/overrides/{override}', [FeatureFlagController::class, 'destroyOverride']);
    });
});
