<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <router-link to="/customers" class="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back
                </router-link>
                <h1 class="text-2xl font-bold text-gray-900">{{ customer?.name ?? 'Customer' }}</h1>
            </div>
            <router-link
                v-if="customer && tenantStore.canWrite"
                :to="`/customers/${customer.id}/edit`"
                class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
            >
                Edit
            </router-link>
        </div>

        <div v-if="isLoading" class="text-sm text-gray-500">Loading...</div>

        <div v-else-if="customer" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Name</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ customer.name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">{{ customer.email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Phone</dt>
                    <dd class="text-sm text-gray-900">{{ customer.phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Company</dt>
                    <dd class="text-sm text-gray-900">{{ customer.company ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd>
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
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Created</dt>
                    <dd class="text-sm text-gray-900">
                        {{ new Date(customer.created_at).toLocaleDateString() }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { customerApi } from '@/api';
import { useTenantStore } from '@/stores/tenant';
import type { Customer } from '@/api/types';

const route = useRoute();
const tenantStore = useTenantStore();
const customer = ref<Customer | null>(null);
const isLoading = ref(true);

onMounted(async () => {
    try {
        const { data } = await customerApi.show(Number(route.params.id));
        customer.value = data.data;
    } finally {
        isLoading.value = false;
    }
});
</script>
