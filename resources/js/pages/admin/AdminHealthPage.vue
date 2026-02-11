<template>
    <div>
        <h1 class="text-2xl font-bold text-white mb-6">System Health</h1>

        <div class="flex items-center gap-3 mb-6">
            <button
                @click="checkHealth"
                :disabled="isLoading"
                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-md disabled:opacity-50"
            >
                {{ isLoading ? 'Checking...' : 'Run Health Check' }}
            </button>
            <span v-if="lastChecked" class="text-sm text-gray-400"
                >Last checked: {{ lastChecked }}</span
            >
        </div>

        <div v-if="checks.length > 0" class="space-y-4">
            <div
                v-for="check in checks"
                :key="check.name"
                class="bg-gray-800 rounded-lg border border-gray-700 p-4 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-3 h-3 rounded-full"
                        :class="
                            check.status === 'ok'
                                ? 'bg-green-400'
                                : check.status === 'warning'
                                  ? 'bg-yellow-400'
                                  : 'bg-red-400'
                        "
                    ></div>
                    <div>
                        <p class="text-sm font-medium text-white">{{ check.name }}</p>
                        <p v-if="check.message" class="text-xs text-gray-400">
                            {{ check.message }}
                        </p>
                    </div>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(check.status)">{{
                    check.status
                }}</span>
            </div>
        </div>

        <div
            v-else-if="!isLoading"
            class="bg-gray-800 rounded-lg border border-gray-700 p-8 text-center"
        >
            <p class="text-sm text-gray-400">Click "Run Health Check" to check system status.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';
import { useNotificationStore } from '@/stores/notification';

interface HealthCheckResult {
    name: string;
    status: string;
    message?: string;
}

const notifications = useNotificationStore();
const isLoading = ref(false);
const checks = ref<HealthCheckResult[]>([]);
const lastChecked = ref('');

function statusClass(status: string): string {
    const map: Record<string, string> = {
        ok: 'bg-green-900/50 text-green-400',
        warning: 'bg-yellow-900/50 text-yellow-400',
        error: 'bg-red-900/50 text-red-400',
    };
    return map[status] ?? 'bg-gray-700 text-gray-300';
}

async function checkHealth(): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await apiClient.get('/admin/health');
        const result = data.data ?? data;
        if (Array.isArray(result)) {
            checks.value = result as HealthCheckResult[];
        } else if (result.checks) {
            checks.value = result.checks as HealthCheckResult[];
        } else {
            checks.value = Object.entries(result).map(([name, val]: [string, unknown]) => {
                if (typeof val === 'object' && val !== null)
                    return { name, ...(val as Record<string, unknown>) } as HealthCheckResult;
                return {
                    name,
                    status: val === true || val === 'ok' ? 'ok' : 'error',
                } as HealthCheckResult;
            });
        }
        lastChecked.value = new Date().toLocaleTimeString();
    } catch {
        notifications.error('Health check failed.');
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => checkHealth());
</script>
