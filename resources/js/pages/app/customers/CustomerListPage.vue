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

        <!-- Saved Views -->
        <div class="flex items-center gap-2 mb-4 overflow-x-auto">
            <button
                :class="[
                    'px-3 py-1.5 text-xs font-medium rounded-full border whitespace-nowrap transition-colors',
                    !activeViewId ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400',
                ]"
                @click="clearView"
            >
                All Customers
            </button>
            <button
                v-for="view in savedViews"
                :key="view.id"
                :class="[
                    'px-3 py-1.5 text-xs font-medium rounded-full border whitespace-nowrap transition-colors',
                    activeViewId === view.id ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400',
                ]"
                @click="applyView(view)"
            >
                {{ view.name }}
            </button>
            <button
                v-if="tenantStore.canWrite"
                class="px-3 py-1.5 text-xs font-medium rounded-full border border-dashed border-gray-300 text-gray-400 hover:text-gray-600 hover:border-gray-400 whitespace-nowrap"
                @click="showSaveView = true"
            >
                + Save View
            </button>
        </div>

        <!-- Save View Modal -->
        <div v-if="showSaveView" class="mb-4 p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
            <form @submit.prevent="saveView" class="flex items-end gap-3">
                <BaseInput v-model="newViewName" label="View Name" placeholder="e.g. Active VIPs" id="view-name" class="flex-1 max-w-xs" />
                <BaseButton type="submit" variant="primary" size="sm" :loading="savingView">Save</BaseButton>
                <BaseButton variant="ghost" size="sm" @click="showSaveView = false">Cancel</BaseButton>
            </form>
        </div>

        <!-- Filters & Search -->
        <div class="flex items-center gap-3 mb-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search customers..."
                class="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                @input="debouncedFetch"
            />

            <!-- Status Filter -->
            <select
                v-model="statusFilter"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                @change="fetchCustomers(1)"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Sort -->
            <select
                v-model="sortField"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                @change="fetchCustomers(1)"
            >
                <option value="name">Name</option>
                <option value="created_at">Created</option>
                <option value="email">Email</option>
                <option value="company">Company</option>
            </select>

            <button
                class="p-2 rounded-md border border-gray-300 text-gray-500 hover:text-gray-700 text-sm"
                @click="toggleSortDir"
                :title="sortDir === 'asc' ? 'Ascending' : 'Descending'"
            >
                {{ sortDir === 'asc' ? '↑' : '↓' }}
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" @click="sortBy('name')">
                            Name <span v-if="sortField === 'name'" class="text-gray-400">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" @click="sortBy('email')">
                            Email <span v-if="sortField === 'email'" class="text-gray-400">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" @click="sortBy('status')">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" @click="sortBy('created_at')">
                            Created <span v-if="sortField === 'created_at'" class="text-gray-400">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="isLoading">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Loading...</td>
                    </tr>
                    <tr v-else-if="customers.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No customers found.</td>
                    </tr>
                    <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <router-link :to="`/customers/${customer.id}`" class="hover:underline">{{ customer.name }}</router-link>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ customer.email ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ customer.company ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', customer.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800']">
                                {{ customer.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(customer.created_at) }}</td>
                        <td class="px-6 py-4 text-right">
                            <router-link v-if="tenantStore.canWrite" :to="`/customers/${customer.id}/edit`" class="text-sm text-gray-600 hover:text-gray-900">Edit</router-link>
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
                <button :disabled="pagination.currentPage <= 1" @click="fetchCustomers(pagination.currentPage - 1)" class="px-3 py-1 text-sm border rounded-md disabled:opacity-50">Previous</button>
                <button :disabled="pagination.currentPage >= pagination.lastPage" @click="fetchCustomers(pagination.currentPage + 1)" class="px-3 py-1 text-sm border rounded-md disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { customerApi, savedViewApi } from '@/api';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { Customer, SavedView } from '@/api/types';

const tenantStore = useTenantStore();
const notifications = useNotificationStore();

const customers = ref<Customer[]>([]);
const isLoading = ref(false);
const search = ref('');
const statusFilter = ref('');
const sortField = ref('name');
const sortDir = ref<'asc' | 'desc'>('asc');

// Saved views
const savedViews = ref<SavedView[]>([]);
const activeViewId = ref<number | null>(null);
const showSaveView = ref(false);
const newViewName = ref('');
const savingView = ref(false);

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: 0,
    to: 0,
});

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function sortBy(field: string): void {
    if (sortField.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDir.value = 'asc';
    }
    fetchCustomers(1);
}

function toggleSortDir(): void {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    fetchCustomers(1);
}

async function fetchCustomers(page = 1): Promise<void> {
    isLoading.value = true;
    try {
        const params: Record<string, unknown> = {
            page,
            sort: sortField.value,
            direction: sortDir.value,
        };
        if (search.value) params.search = search.value;
        if (statusFilter.value) params['filter[status]'] = statusFilter.value;

        const { data } = await customerApi.list(params);
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

async function loadSavedViews(): Promise<void> {
    try {
        const { data } = await savedViewApi.list('customer');
        savedViews.value = data.data;
    } catch {
        // Silent — views are optional
    }
}

function applyView(view: SavedView): void {
    activeViewId.value = view.id;
    const config = view.config as Record<string, string>;
    search.value = config.search ?? '';
    statusFilter.value = config.status ?? '';
    sortField.value = config.sort ?? 'name';
    sortDir.value = (config.direction as 'asc' | 'desc') ?? 'asc';
    fetchCustomers(1);
}

function clearView(): void {
    activeViewId.value = null;
    search.value = '';
    statusFilter.value = '';
    sortField.value = 'name';
    sortDir.value = 'asc';
    fetchCustomers(1);
}

async function saveView(): Promise<void> {
    if (!newViewName.value.trim()) return;
    savingView.value = true;
    try {
        const { data } = await savedViewApi.create({
            resource_type: 'customer',
            name: newViewName.value,
            config: {
                search: search.value,
                status: statusFilter.value,
                sort: sortField.value,
                direction: sortDir.value,
            },
        });
        savedViews.value.push(data.data);
        newViewName.value = '';
        showSaveView.value = false;
        notifications.success('View saved.');
    } catch {
        notifications.error('Failed to save view.');
    } finally {
        savingView.value = false;
    }
}

const debouncedFetch = useDebounceFn(() => fetchCustomers(1), 300);

onMounted(() => {
    fetchCustomers();
    loadSavedViews();
});
</script>
