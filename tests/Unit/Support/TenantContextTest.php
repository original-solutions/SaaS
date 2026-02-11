<?php

use App\Support\Tenancy\TenantContext;

it('can set and get tenant', function (): void {
    $ctx = app(TenantContext::class);
    $tenant = (object) ['id' => 42, 'name' => 'Test'];

    $ctx->set($tenant);

    expect($ctx->has())->toBeTrue();
    expect($ctx->get())->toBe($tenant);
    expect($ctx->id())->toBe(42);
});

it('returns false for has() when no tenant set', function (): void {
    $ctx = app(TenantContext::class);

    expect($ctx->has())->toBeFalse();
    expect($ctx->get())->toBeNull();
});

it('can clear tenant', function (): void {
    $ctx = app(TenantContext::class);
    $ctx->set((object) ['id' => 1]);

    $ctx->clear();

    expect($ctx->has())->toBeFalse();
});
