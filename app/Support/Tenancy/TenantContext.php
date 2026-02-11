<?php

namespace App\Support\Tenancy;

class TenantContext
{
    protected ?int $tenantId = null;

    protected ?object $tenant = null;

    /**
     * Set the current tenant context.
     */
    public function set(object $tenant): void
    {
        $this->tenant = $tenant;
        $this->tenantId = $tenant->id;
    }

    /**
     * Get the current tenant.
     */
    public function get(): ?object
    {
        return $this->tenant;
    }

    /**
     * Get the current tenant ID.
     */
    public function id(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Check if a tenant context is set.
     */
    public function has(): bool
    {
        return $this->tenantId !== null;
    }

    /**
     * Clear the tenant context.
     */
    public function clear(): void
    {
        $this->tenant = null;
        $this->tenantId = null;
    }
}
