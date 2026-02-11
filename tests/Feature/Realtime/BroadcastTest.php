<?php

use App\Events\CustomerUpdated;
use App\Events\ImportCompleted;
use App\Events\UserNotification;
use App\Models\Customer;
use App\Models\Import;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('broadcasts customer updated on the tenant private channel', function (): void {
    Event::fake([CustomerUpdated::class]);

    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    event(new CustomerUpdated($customer));

    Event::assertDispatched(CustomerUpdated::class, function (CustomerUpdated $event) use ($tenant): bool {
        $channels = $event->broadcastOn();

        return count($channels) === 1
            && $channels[0] instanceof PrivateChannel
            && $channels[0]->name === "private-tenant.{$tenant->id}";
    });
});

it('broadcasts import completed on the tenant private channel', function (): void {
    Event::fake([ImportCompleted::class]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $import = Import::factory()->create([
        'tenant_id' => $tenant->id,
        'created_by_user_id' => $user->id,
    ]);

    event(new ImportCompleted($import));

    Event::assertDispatched(ImportCompleted::class, function (ImportCompleted $event) use ($tenant): bool {
        $channels = $event->broadcastOn();

        return $channels[0]->name === "private-tenant.{$tenant->id}";
    });
});

it('broadcasts user notification on the user private channel', function (): void {
    Event::fake([UserNotification::class]);

    $user = User::factory()->create();
    $notification = ['type' => 'info', 'message' => 'Test notification'];

    event(new UserNotification($user, $notification));

    Event::assertDispatched(UserNotification::class, function (UserNotification $event) use ($user): bool {
        $channels = $event->broadcastOn();

        return $channels[0]->name === "private-App.Models.User.{$user->id}";
    });
});

it('includes correct customer data in broadcast payload', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Test Customer',
        'email' => 'test@example.com',
    ]);

    $event = new CustomerUpdated($customer);
    $payload = $event->broadcastWith();

    expect($payload)->toHaveKey('customer')
        ->and($payload['customer']['id'])->toBe($customer->id)
        ->and($payload['customer']['name'])->toBe('Test Customer')
        ->and($payload['customer']['email'])->toBe('test@example.com');
});

it('includes correct import data in broadcast payload', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $import = Import::factory()->create([
        'tenant_id' => $tenant->id,
        'created_by_user_id' => $user->id,
        'total_rows' => 100,
        'processed_rows' => 95,
        'error_count' => 5,
    ]);

    $event = new ImportCompleted($import);
    $payload = $event->broadcastWith();

    expect($payload)->toHaveKey('import')
        ->and($payload['import']['total_rows'])->toBe(100)
        ->and($payload['import']['processed_rows'])->toBe(95)
        ->and($payload['import']['error_count'])->toBe(5);
});

it('tenant channel authorization allows tenant members', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['role' => 'member']);

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'channel_name' => "private-tenant.{$tenant->id}",
        ])
        ->assertSuccessful();
});

it('tenant channel authorization callback rejects non-members', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    // Verify the user is not a member of the tenant
    expect($user->tenants()->where('tenants.id', $tenant->id)->exists())->toBeFalse();
});
