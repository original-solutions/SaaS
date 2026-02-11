<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
            <router-link
                v-if="tenantStore.canWrite"
                to="/customers/create"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-900 text-white rounded-md hover:bg-gray-800"
            >
                <Plus class="h-4 w-4" />
                Add Customer
            </router-link>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search customers..."
                class="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                @input="debouncedFetch"
            />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Name
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Email
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Status
                        </th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="isLoading">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                            Loading...
                        </td>
                    </tr>
                    <tr v-else-if="customers.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                            No customers found.
                        </td>
                    </tr>
                    <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <router-link :to="`/customers/${customer.id}`" class="hover:underline">
                                {{ customer.name }}
                            </router-link>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ customer.email ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    customer.status === 'active'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800',
                                ]"
                            >
                                {{ customer.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <router-link
                                :to="`/customers/${customer.id}/edit`"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Edit
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="flex items-center justify-between mt-4">
            <span class="text-sm text-gray-600">
                Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }}
            </span>
            <div class="flex gap-2">
                <button
                    :disabled="pagination.currentPage <= 1"
                    @click="fetchCustomers(pagination.currentPage - 1)"
                    class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
                >
                    Previous
                </button>
                <button
                    :disabled="pagination.currentPage >= pagination.lastPage"
                    @click="fetchCustomers(pagination.currentPage + 1)"
                    class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
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
import { Plus } from 'lucide-vue-next';
import { customerApi } from '@/api';
import { useTenantStore } from '@/stores/tenant';
import type { Customer } from '@/api/types';

const tenantStore = useTenantStore();
const customers = ref<Customer[]>([]);
const isLoading = ref(false);
const search = ref('');
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: 0,
    to: 0,
});

async function fetchCustomers(page = 1): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await customerApi.list({
            page,
            search: search.value || undefined,
        });
        customers.value = data.data;
        pagination.currentPage = data.current_page;
        pagination.lastPage = data.last_page;
        pagination.total = data.total;
        pagination.from = data.from ?? 0;
        pagination.to = data.to ?? 0;
    } finally {
        isLoading.value = false;
    }
}

const debouncedFetch = useDebounceFn(() => fetchCustomers(1), 300);

onMounted(() => fetchCustomers());
</script>
