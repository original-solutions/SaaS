<?php

it('returns healthy status when all checks pass', function (): void {
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/health')
        ->assertSuccessful()
        ->assertJsonPath('status', 'healthy')
        ->assertJsonStructure(['status', 'checks' => ['database', 'queue', 'broadcast']]);
});
