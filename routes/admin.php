<?php

use App\Http\Controllers\Admin\FeatureFlagController;
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

Route::middleware('auth:sanctum')->group(function (): void {
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
