<?php

namespace App\Services;

use App\Models\Tenant;

class TenantUsageStatsService
{
    /**
     * Get usage statistics for a tenant.
     *
     * @return array<string, mixed>
     */
    public function getStats(Tenant $tenant): array
    {
        return [
            'member_count' => $tenant->users()->count(),
            'file_count' => \App\Models\File::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
            'note_count' => \App\Models\Note::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
            'tag_count' => \App\Models\Tag::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
            'import_count' => \App\Models\Import::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
            'storage_used_bytes' => \App\Models\File::withoutGlobalScopes()->where('tenant_id', $tenant->id)->sum('size_bytes'),
            'last_activity' => \App\Models\Activity::where('tenant_id', $tenant->id)->latest()->first()?->created_at,
            'status' => $tenant->status->value,
            'plan' => $tenant->plan,
        ];
    }
}
