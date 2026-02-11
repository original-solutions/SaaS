<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Team settings</h1>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                {{ tenantStore.current?.name }}
            </h2>
            <p class="text-sm text-gray-600">
                Manage team members, invitations, and workspace settings.
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useTenantStore } from '@/stores/tenant';

const tenantStore = useTenantStore();
</script>
