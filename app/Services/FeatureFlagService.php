<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\FeatureFlagOverride;
use App\Models\Tenant;
use App\Models\User;

class FeatureFlagService
{
    /**
     * Check if a feature flag is enabled.
     * Override precedence: user > tenant > default.
     */
    public function isEnabled(string $key, ?Tenant $tenant = null, ?User $user = null): bool
    {
        $flag = FeatureFlag::where('key', $key)->first();

        if (! $flag) {
            return false;
        }

        // Check user-level override first
        if ($user) {
            $userOverride = FeatureFlagOverride::where('feature_flag_id', $flag->id)
                ->where('user_id', $user->id)
                ->first();

            if ($userOverride) {
                return $userOverride->enabled;
            }
        }

        // Check tenant-level override
        if ($tenant) {
            $tenantOverride = FeatureFlagOverride::where('feature_flag_id', $flag->id)
                ->where('tenant_id', $tenant->id)
                ->whereNull('user_id')
                ->first();

            if ($tenantOverride) {
                return $tenantOverride->enabled;
            }
        }

        // Return default
        return $flag->enabled;
    }
}
