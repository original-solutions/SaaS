<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('sends verification email', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/resend');

    $response->assertSuccessful()
        ->assertJson(['message' => 'Verification link sent.']);

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('returns message if already verified', function (): void {
    Notification::fake();

    $user = User::factory()->create(); // verified by default

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/resend');

    $response->assertSuccessful()
        ->assertJson(['message' => 'Email already verified.']);

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

it('requires authentication for verification resend', function (): void {
    $response = $this->postJson('/api/v1/auth/email/resend');

    $response->assertUnauthorized();
});
