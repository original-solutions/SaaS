<template>
    <div>
        <div class="flex items-center gap-3 mb-6">
            <router-link to="/billing" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </router-link>
            <h1 class="text-2xl font-bold text-gray-900">Available Plans</h1>
        </div>

        <div v-if="isLoading" class="text-sm text-gray-500">Loading plans...</div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div
                v-for="plan in plans"
                :key="plan.id"
                :class="[
                    'bg-white rounded-lg shadow-sm border p-6 flex flex-col',
                    plan.is_active ? 'border-indigo-200 ring-2 ring-indigo-100' : 'border-gray-200',
                ]"
            >
                <h3 class="text-lg font-semibold text-gray-900">{{ plan.name }}</h3>
                <p class="text-sm text-gray-500 mt-1 mb-4">{{ planDescription(plan) }}</p>

                <!-- Features -->
                <ul class="space-y-2 mb-6 flex-1">
                    <li v-for="(value, key) in plan.features" :key="String(key)" class="flex items-start gap-2 text-sm">
                        <svg class="h-4 w-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <span class="text-gray-700">{{ formatFeature(String(key), value) }}</span>
                    </li>
                </ul>

                <BaseButton variant="primary" class="w-full" disabled>
                    {{ plan.is_active ? 'Current Plan' : 'Select Plan' }}
                </BaseButton>
                <p class="text-xs text-gray-400 text-center mt-2">Billing integration coming soon</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { billingApi } from '@/api';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { Plan } from '@/api/types';

const plans = ref<Plan[]>([]);
const isLoading = ref(true);

function planDescription(plan: Plan): string {
    const descriptions: Record<string, string> = {
        Starter: 'For individuals and small teams getting started.',
        Professional: 'For growing businesses that need more power.',
        Enterprise: 'For large organizations with advanced needs.',
    };
    return descriptions[plan.name] ?? 'A plan for your needs.';
}

function formatFeature(key: string, value: unknown): string {
    const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    if (typeof value === 'boolean') return value ? label : `No ${label.toLowerCase()}`;
    if (typeof value === 'number') return `${value} ${label}`;
    return `${label}: ${value}`;
}

async function loadPlans(): Promise<void> {
    try {
        const { data } = await billingApi.plans();
        plans.value = data.data;
    } finally {
        isLoading.value = false;
    }
}

onMounted(loadPlans);
</script>
