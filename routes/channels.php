<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('tenant.{tenantId}', function (User $user, int $tenantId): bool {
    return $user->tenants()->where('tenants.id', $tenantId)->exists();
});

Broadcast::channel('tenant.{tenantId}.presence', function (User $user, int $tenantId): array|false {
    if (! $user->tenants()->where('tenants.id', $tenantId)->exists()) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
