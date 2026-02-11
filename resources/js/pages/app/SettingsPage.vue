<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Settings</h1>

        <div class="space-y-6">
            <!-- Profile -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile</h2>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">Name:</span> {{ authStore.user?.name }}</div>
                    <div><span class="text-gray-500">Email:</span> {{ authStore.user?.email }}</div>
                </div>
            </div>

            <!-- Security -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Security</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Two-factor authentication</span>
                        <span
                            :class="authStore.hasTwoFactor ? 'text-green-600' : 'text-gray-400'"
                            class="text-sm"
                        >
                            {{ authStore.hasTwoFactor ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sessions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Device sessions</h2>
                <p class="text-sm text-gray-600">Manage your active sessions across devices.</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
</script>
