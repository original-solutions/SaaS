<?php

namespace App\Services;

use App\Models\SupportCase;
use App\Models\Tenant;
use App\Models\User;

class AdminSearchService
{
    /**
     * Search across tenants, users, and support cases.
     *
     * @return array{tenants: array<mixed>, users: array<mixed>, support_cases: array<mixed>}
     */
    public function search(string $query): array
    {
        $tenants = Tenant::withoutGlobalScopes()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('slug', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        $supportCases = SupportCase::where('subject', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return [
            'tenants' => $tenants->toArray(),
            'users' => $users->toArray(),
            'support_cases' => $supportCases->toArray(),
        ];
    }
}
