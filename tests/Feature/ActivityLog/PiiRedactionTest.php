<?php

use App\Models\Activity;
use App\Models\User;

it('redacts PII fields from activity log properties', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    // Manually create an activity with PII in properties
    activity()
        ->causedBy($user)
        ->withProperties([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        ])
        ->log('test-with-pii');

    $log = Activity::latest()->first();

    expect($log->properties['password'])->toBe('[REDACTED]')
        ->and($log->properties['two_factor_secret'])->toBe('[REDACTED]')
        ->and($log->properties['email'])->toBe('test@example.com'); // email not in denylist
});

it('redacts nested PII fields', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    activity()
        ->causedBy($user)
        ->withProperties([
            'attributes' => [
                'name' => 'John',
                'password' => 'secret',
                'token' => 'abc123',
            ],
        ])
        ->log('nested-pii');

    $log = Activity::latest()->first();

    expect($log->properties['attributes']['name'])->toBe('John')
        ->and($log->properties['attributes']['password'])->toBe('[REDACTED]')
        ->and($log->properties['attributes']['token'])->toBe('[REDACTED]');
});

it('does not redact non-PII fields', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    activity()
        ->causedBy($user)
        ->withProperties([
            'name' => 'Test',
            'slug' => 'test-slug',
            'status' => 'active',
        ])
        ->log('no-pii');

    $log = Activity::latest()->first();

    expect($log->properties['name'])->toBe('Test')
        ->and($log->properties['slug'])->toBe('test-slug')
        ->and($log->properties['status'])->toBe('active');
});
