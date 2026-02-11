<template>
    <div>
        <h1 class="text-2xl font-bold text-white mb-6">Users</h1>

        <div class="mb-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search users..."
                class="w-full max-w-sm rounded-md border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none"
                @input="debouncedFetch"
            />
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-700">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Tenants
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Last Login
                        </th>
                        <th class="px-6 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <tr v-if="isLoading">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">
                            Loading...
                        </td>
                    </tr>
                    <tr v-else-if="users.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">
                            No users.
                        </td>
                    </tr>
                    <tr v-for="u in users" :key="u.id" class="hover:bg-gray-700/50">
                        <td class="px-6 py-4 text-sm font-medium text-white">
                            <router-link :to="`/admin/users/${u.id}`" class="hover:underline">
                                {{ u.name }}
                            </router-link>
                            <span v-if="u.is_super_admin" class="ml-1 text-xs text-indigo-400"
                                >(admin)</span
                            >
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-300">
                            {{ u.email }}
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="u.locked_at" class="text-xs text-red-400">Locked</span>
                            <span v-else class="text-xs text-green-400">Active</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-300">
                            {{ u.tenants_count ?? 0 }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            {{ u.last_login_at ? formatDate(u.last_login_at) : '—' }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button
                                v-if="u.locked_at"
                                class="text-xs text-green-400 hover:text-green-300"
                                @click="unlockUser(u)"
                            >
                                Unlock
                            </button>
                            <button
                                v-else
                                class="text-xs text-red-400 hover:text-red-300"
                                @click="lockUser(u)"
                            >
                                Lock
                            </button>
                            <button
                                class="text-xs text-indigo-400 hover:text-indigo-300"
                                @click="impersonateUser(u)"
                            >
                                Impersonate
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination.lastPage > 1" class="flex items-center justify-between mt-4">
            <span class="text-sm text-gray-400"
                >Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span
            >
            <div class="flex gap-2">
                <button
                    :disabled="pagination.currentPage <= 1"
                    class="px-3 py-1 text-sm border border-gray-600 rounded-md text-gray-300 disabled:opacity-50"
                    @click="fetchUsers(pagination.currentPage - 1)"
                >
                    Previous
                </button>
                <button
                    :disabled="pagination.currentPage >= pagination.lastPage"
                    class="px-3 py-1 text-sm border border-gray-600 rounded-md text-gray-300 disabled:opacity-50"
                    @click="fetchUsers(pagination.currentPage + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useDebounceFn } from '@vueuse/core';
import { adminUsersApi } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import type { AdminUserDetail } from '@/api/types';

const router = useRouter();
const authStore = useAuthStore();
const notifications = useNotificationStore();

const users = ref<AdminUserDetail[]>([]);
const isLoading = ref(true);
const search = ref('');
const pagination = reactive({ currentPage: 1, lastPage: 1 });

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

async function fetchUsers(page = 1): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await adminUsersApi.list({ page, search: search.value || undefined });
        users.value = data.data;
        pagination.currentPage = data.current_page;
        pagination.lastPage = data.last_page;
    } finally {
        isLoading.value = false;
    }
}

async function lockUser(u: AdminUserDetail): Promise<void> {
    try {
        await adminUsersApi.lock(u.id);
        u.locked_at = new Date().toISOString();
        notifications.success(`${u.name} locked.`);
    } catch {
        notifications.error('Failed to lock user.');
    }
}

async function unlockUser(u: AdminUserDetail): Promise<void> {
    try {
        await adminUsersApi.unlock(u.id);
        u.locked_at = null;
        notifications.success(`${u.name} unlocked.`);
    } catch {
        notifications.error('Failed to unlock user.');
    }
}

async function impersonateUser(u: AdminUserDetail): Promise<void> {
    try {
        const { data } = await adminUsersApi.impersonate(u.id);
        const payload = data.data;
        authStore.startImpersonation(payload.access_token, payload.expires_at);
        await authStore.fetchUser();
        notifications.info(`Now impersonating ${u.name}.`);
        router.push('/');
    } catch {
        notifications.error('Failed to impersonate.');
    }
}

const debouncedFetch = useDebounceFn(() => fetchUsers(1), 300);

onMounted(() => fetchUsers());
</script>
