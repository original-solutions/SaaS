<?php

it('returns recent super-admin actions', function (): void {
    actingAsSuperAdmin();

    $this->getJson('/admin/api/v1/dashboard')
        ->assertSuccessful()
        ->assertJsonStructure(['data']);
});
