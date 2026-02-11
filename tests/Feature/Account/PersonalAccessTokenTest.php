<?php

use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('lists personal access tokens', function (): void {
    $this->user->createToken('test-token-1');
    $this->user->createToken('test-token-2');

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/account/tokens');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('creates a personal access token', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/account/tokens', [
            'name' => 'My API Token',
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'name', 'abilities']);

    expect($response->json('name'))->toBe('My API Token');
    expect($response->json('token'))->not->toBeEmpty();
});

it('returns plain text token only once on creation', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/account/tokens', [
            'name' => 'My API Token',
        ]);

    $plainToken = $response->json('token');
    expect($plainToken)->toContain('|');
});

it('creates token with specific abilities', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/account/tokens', [
            'name' => 'Read Only Token',
            'abilities' => ['read'],
        ]);

    $response->assertCreated();
    expect($response->json('abilities'))->toBe(['read']);
});

it('revokes a personal access token', function (): void {
    $token = $this->user->createToken('test-token');

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v1/account/tokens/{$token->accessToken->id}");

    $response->assertSuccessful()
        ->assertJson(['message' => 'Token revoked.']);

    expect($this->user->tokens()->count())->toBe(0);
});

it('returns 404 when revoking non-existent token', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson('/api/v1/account/tokens/99999');

    $response->assertNotFound();
});

it('cannot revoke another user token', function (): void {
    $otherUser = User::factory()->create();
    $token = $otherUser->createToken('other-token');

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v1/account/tokens/{$token->accessToken->id}");

    $response->assertNotFound();
});

it('token authenticates API requests', function (): void {
    $token = $this->user->createToken('api-token');

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/me');

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $this->user->id);
});

it('validates name is required', function (): void {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/account/tokens', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/account/tokens')->assertUnauthorized();
    $this->postJson('/api/v1/account/tokens', ['name' => 'test'])->assertUnauthorized();
    $this->deleteJson('/api/v1/account/tokens/1')->assertUnauthorized();
});
