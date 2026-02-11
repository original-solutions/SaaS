<template>
    <div>
        <h1 class="text-2xl font-bold text-white mb-6">Tenants</h1>

        <!-- Search -->
        <div class="mb-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search tenants..."
                class="w-full max-w-sm rounded-md border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none"
                @input="debouncedFetch"
            />
        </div>

        <!-- Table -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-700">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Users
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Customers
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Created
                        </th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <tr v-if="isLoading">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">
                            Loading...
                        </td>
                    </tr>
                    <tr v-else-if="tenants.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">
                            No tenants.
                        </td>
                    </tr>
                    <tr v-for="t in tenants" :key="t.id" class="hover:bg-gray-700/50">
                        <td class="px-6 py-4 text-sm font-medium text-white">
                            <router-link :to="`/admin/tenants/${t.id}`" class="hover:underline">{{
                                t.name
                            }}</router-link>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="statusClass(t.status)"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                >{{ t.status }}</span
                            >
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-300">{{ t.users_count ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-300">
                            {{ t.customers_count ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            {{ formatDate(t.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button
                                v-if="t.disabled_at"
                                @click="enableTenant(t)"
                                class="text-xs text-green-400 hover:text-green-300"
                            >
                                Enable
                            </button>
                            <button
                                v-else
                                @click="disableTenant(t)"
                                class="text-xs text-red-400 hover:text-red-300"
                            >
                                Disable
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="flex items-center justify-between mt-4">
            <span class="text-sm text-gray-400"
                >Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span
            >
            <div class="flex gap-2">
                <button
                    :disabled="pagination.currentPage <= 1"
                    @click="fetchTenants(pagination.currentPage - 1)"
                    class="px-3 py-1 text-sm border border-gray-600 rounded-md text-gray-300 disabled:opacity-50"
                >
                    Previous
                </button>
                <button
                    :disabled="pagination.currentPage >= pagination.lastPage"
                    @click="fetchTenants(pagination.currentPage + 1)"
                    class="px-3 py-1 text-sm border border-gray-600 rounded-md text-gray-300 disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { adminTenantsApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import type { AdminTenantDetail } from '@/api/types';

const notifications = useNotificationStore();
const tenants = ref<AdminTenantDetail[]>([]);
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

function statusClass(status: string): string {
    const map: Record<string, string> = {
        active: 'bg-green-500/20 text-green-400',
        inactive: 'bg-gray-500/20 text-gray-400',
        suspended: 'bg-red-500/20 text-red-400',
    };
    return map[status] ?? 'bg-gray-500/20 text-gray-400';
}

async function fetchTenants(page = 1): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await adminTenantsApi.list({ page, search: search.value || undefined });
        tenants.value = data.data;
        pagination.currentPage = data.current_page;
        pagination.lastPage = data.last_page;
    } finally {
        isLoading.value = false;
    }
}

async function disableTenant(t: AdminTenantDetail): Promise<void> {
    try {
        await adminTenantsApi.disable(t.id);
        t.disabled_at = new Date().toISOString();
        t.status = 'suspended';
        notifications.success(`${t.name} disabled.`);
    } catch {
        notifications.error('Failed to disable tenant.');
    }
}

async function enableTenant(t: AdminTenantDetail): Promise<void> {
    try {
        await adminTenantsApi.enable(t.id);
        t.disabled_at = null;
        t.status = 'active';
        notifications.success(`${t.name} enabled.`);
    } catch {
        notifications.error('Failed to enable tenant.');
    }
}

const debouncedFetch = useDebounceFn(() => fetchTenants(1), 300);

onMounted(() => fetchTenants());
</script>
