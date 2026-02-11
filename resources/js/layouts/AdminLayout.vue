<template>
    <div class="min-h-screen bg-gray-900">
        <!-- Admin navigation -->
        <nav class="bg-gray-800 border-b border-gray-700">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center gap-8">
                        <router-link to="/admin" class="text-xl font-bold text-white">
                            Admin
                        </router-link>
                        <div class="hidden sm:flex items-center gap-4">
                            <router-link
                                v-for="item in navItems"
                                :key="item.to"
                                :to="item.to"
                                class="text-sm text-gray-300 hover:text-white px-3 py-2 rounded-md transition-colors"
                                active-class="text-white bg-gray-700"
                            >
                                {{ item.label }}
                            </router-link>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <router-link to="/" class="text-sm text-gray-300 hover:text-white">
                            &larr; Back to App
                        </router-link>
                        <span class="text-sm text-gray-400">
                            {{ authStore.user?.name }}
                        </span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page content -->
        <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
            <router-view />
        </main>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const navItems = [
    { to: '/admin', label: 'Dashboard' },
    { to: '/admin/tenants', label: 'Tenants' },
    { to: '/admin/users', label: 'Users' },
];
</script>
