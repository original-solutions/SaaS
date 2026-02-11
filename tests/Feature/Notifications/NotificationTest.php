<?php

use App\Models\User;

it('lists paginated notifications for the authenticated user', function (): void {
    $user = User::factory()->create();

    // Create some database notifications
    for ($i = 0; $i < 3; $i++) {
        $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => "Notification {$i}"],
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('marks a notification as read', function (): void {
    $user = User::factory()->create();
    $notificationId = \Illuminate\Support\Str::uuid()->toString();

    $user->notifications()->create([
        'id' => $notificationId,
        'type' => 'App\Notifications\TestNotification',
        'data' => ['message' => 'Test'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/notifications/{$notificationId}/read");

    $response->assertSuccessful();

    expect($user->notifications()->find($notificationId)->read_at)->not->toBeNull();
});

it('marks all notifications as read', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => "Notification {$i}"],
        ]);
    }

    expect($user->unreadNotifications)->toHaveCount(3);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/notifications/read-all');

    $response->assertSuccessful();

    $user->refresh();
    expect($user->unreadNotifications)->toHaveCount(0);
});

it('lists notification preferences', function (): void {
    $user = User::factory()->create();
    $tenant = \App\Models\Tenant::factory()->create();

    \App\Models\NotificationPreference::create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'key' => 'invitation_received',
        'channels' => ['email', 'database'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications/preferences?tenant_id='.$tenant->id);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('updates a notification preference', function (): void {
    $user = User::factory()->create();
    $tenant = \App\Models\Tenant::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->withHeader('X-Tenant-ID', $tenant->id)
        ->putJson('/api/v1/notifications/preferences', [
            'key' => 'invitation_received',
            'channels' => ['email'],
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'key' => 'invitation_received',
    ]);
});
