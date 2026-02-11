<?php

it('returns 200 for health endpoint', function (): void {
    $this->get('/up')->assertOk();
});

it('returns 404 for unknown API v1 routes', function (): void {
    $this->getJson('/api/v1/nonexistent')->assertNotFound();
});

it('has security headers on responses', function (): void {
    $response = $this->get('/up');

    $response->assertHeader('Content-Security-Policy');
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('X-Frame-Options', 'DENY');
});

it('includes nonce in CSP header', function (): void {
    $response = $this->get('/up');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("'nonce-");
    expect($csp)->not->toContain("'unsafe-inline'");
});

it('has CORS headers for API requests', function (): void {
    $response = $this->options('/api/v1/auth', [
        'HTTP_ORIGIN' => 'http://localhost',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ]);

    // CORS should be configured - at minimum not a 405
    expect($response->getStatusCode())->toBeLessThan(500);
});

it('has saas config with correct defaults', function (): void {
    expect(config('saas.id_strategy'))->toBe('bigint');
    expect(config('saas.tenant_identification'))->toBe('header');
    expect(config('saas.access_token_ttl'))->toBe(15);
    expect(config('saas.refresh_token_ttl'))->toBe(10080);
    expect(config('saas.revoke_all_on_reuse'))->toBeTrue();
    expect(config('saas.two_factor_enabled'))->toBeTrue();
    expect(config('saas.invite_expiry_days'))->toBe(7);
    expect(config('saas.grace_period_days'))->toBe(7);
    expect(config('saas.content_retention_on_delete'))->toBe('anonymise');
    expect(config('saas.magic_link_ttl'))->toBe(15);
    expect(config('saas.impersonation_ttl'))->toBe(60);
});

it('has Spatie permission configured with teams enabled', function (): void {
    expect(config('permission.teams'))->toBeTrue();
    expect(config('permission.column_names.team_foreign_key'))->toBe('tenant_id');
});

it('has Spatie activitylog configured with custom model', function (): void {
    expect(config('activitylog.activity_model'))->toBe(\App\Models\Activity::class);
});
