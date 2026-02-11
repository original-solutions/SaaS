<?php

use App\Models\User;

it('rate limits admin impersonation', function (): void {
    $admin = User::factory()->create(['is_super_admin' => true]);

    // 30 attempts per hour per super admin
    for ($i = 0; $i < 30; $i++) {
        $target = User::factory()->create();
        $this->actingAs($admin, 'sanctum')
            ->postJson("/admin/api/v1/impersonate/{$target->id}/start");
    }

    $target = User::factory()->create();
    $this->actingAs($admin, 'sanctum')
        ->postJson("/admin/api/v1/impersonate/{$target->id}/start")
        ->assertStatus(429);
});
