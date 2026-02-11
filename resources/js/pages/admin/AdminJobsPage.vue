<template>
    <div>
        <h1 class="text-2xl font-bold text-white mb-6">Queue &amp; Jobs</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
                <p class="text-sm text-gray-400">Pending</p>
                <p class="text-2xl font-semibold text-white mt-1">{{ stats.pending }}</p>
            </div>
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
                <p class="text-sm text-gray-400">Processing</p>
                <p class="text-2xl font-semibold text-yellow-400 mt-1">{{ stats.processing }}</p>
            </div>
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
                <p class="text-sm text-gray-400">Failed</p>
                <p class="text-2xl font-semibold text-red-400 mt-1">{{ stats.failed }}</p>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-white">Failed Jobs</h2>
            <button
                v-if="failedJobs.length > 0"
                @click="retryAll"
                class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-md"
            >
                Retry All
            </button>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-700">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Queue
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Exception
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                            Failed At
                        </th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <tr v-if="isLoading">
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">
                            Loading...
                        </td>
                    </tr>
                    <tr v-else-if="failedJobs.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">
                            No failed jobs.
                        </td>
                    </tr>
                    <tr v-for="j in failedJobs" :key="j.id" class="hover:bg-gray-700/50">
                        <td class="px-6 py-4 text-sm text-white font-mono">{{ j.id }}</td>
                        <td class="px-6 py-4 text-sm text-gray-300">{{ j.queue }}</td>
                        <td
                            class="px-6 py-4 text-sm text-red-400 truncate max-w-xs"
                            :title="j.exception"
                        >
                            {{ j.exception?.substring(0, 80) }}...
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            {{ formatDate(j.failed_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button
                                @click="retryJob(j.id)"
                                class="text-xs text-indigo-400 hover:text-indigo-300"
                            >
                                Retry
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import apiClient from '@/api/client';
import { useNotificationStore } from '@/stores/notification';

interface FailedJob {
    id: string;
    queue: string;
    exception: string;
    failed_at: string;
}

const notifications = useNotificationStore();
const isLoading = ref(true);
const failedJobs = ref<FailedJob[]>([]);
const stats = reactive({ pending: 0, processing: 0, failed: 0 });

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function fetchData(): Promise<void> {
    isLoading.value = true;
    try {
        const [jobsRes, failedRes] = await Promise.all([
            apiClient.get('/admin/jobs'),
            apiClient.get('/admin/jobs/failed'),
        ]);
        const jobData = jobsRes.data.data ?? jobsRes.data;
        stats.pending = jobData.pending ?? 0;
        stats.processing = jobData.processing ?? 0;
        stats.failed = jobData.failed ?? 0;
        failedJobs.value = failedRes.data.data ?? [];
    } finally {
        isLoading.value = false;
    }
}

async function retryJob(id: string): Promise<void> {
    try {
        await apiClient.post(`/admin/jobs/failed/${id}/retry`);
        failedJobs.value = failedJobs.value.filter((j) => j.id !== id);
        stats.failed = Math.max(0, stats.failed - 1);
        notifications.success('Job queued for retry.');
    } catch {
        notifications.error('Failed to retry job.');
    }
}

async function retryAll(): Promise<void> {
    try {
        await apiClient.post('/admin/jobs/failed/retry-all');
        failedJobs.value = [];
        stats.failed = 0;
        notifications.success('All failed jobs queued for retry.');
    } catch {
        notifications.error('Failed to retry all.');
    }
}

onMounted(() => fetchData());
</script>
