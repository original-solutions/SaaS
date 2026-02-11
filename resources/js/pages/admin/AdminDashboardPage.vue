<template>
  <div>
    <h1 class="text-2xl font-bold text-white mb-6">
      Admin Dashboard
    </h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="bg-gray-800 rounded-lg border border-gray-700 p-6"
      >
        <p class="text-sm text-gray-400">
          {{ stat.label }}
        </p>
        <p class="text-2xl font-bold text-white mt-1">
          {{ stat.value }}
        </p>
      </div>
    </div>

    <!-- Recent Admin Actions -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
      <h2 class="text-lg font-semibold text-white mb-4">
        Recent Admin Actions
      </h2>
      <div
        v-if="loadingActivity"
        class="text-sm text-gray-400"
      >
        Loading...
      </div>
      <div
        v-else-if="recentActions.length === 0"
        class="text-sm text-gray-400"
      >
        No recent admin actions.
      </div>
      <ul
        v-else
        class="space-y-3"
      >
        <li
          v-for="action in recentActions"
          :key="action.id"
          class="flex items-start gap-3"
        >
          <div class="flex-shrink-0 mt-1 h-2 w-2 rounded-full bg-indigo-400" />
          <div>
            <p class="text-sm text-gray-200">
              {{ action.description }}
            </p>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ formatRelative(action.created_at) }}
            </p>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { adminAuditApi, adminTenantsApi, adminUsersApi } from '@/api';
import type { Activity } from '@/api/types';

const stats = ref([
    { label: 'Total Tenants', value: '—' },
    { label: 'Total Users', value: '—' },
    { label: 'Active Subscriptions', value: '—' },
    { label: 'System Health', value: 'OK' },
]);

const recentActions = ref<Activity[]>([]);
const loadingActivity = ref(true);

function formatRelative(dateStr: string): string {
    const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

async function loadStats(): Promise<void> {
    try {
        const [tenants, users] = await Promise.all([
            adminTenantsApi.list({ per_page: 1 }),
            adminUsersApi.list({ per_page: 1 }),
        ]);
        stats.value[0].value = String(tenants.data.total ?? 0);
        stats.value[1].value = String(users.data.total ?? 0);
    } catch {
        // Stats are best-effort
    }
}

async function loadRecentActions(): Promise<void> {
    try {
        const { data } = await adminAuditApi.list({ per_page: 10, log_name: 'admin' });
        recentActions.value = data.data;
    } catch {
        /* silent */
    } finally {
        loadingActivity.value = false;
    }
}

onMounted(() => {
    loadStats();
    loadRecentActions();
});
</script>
