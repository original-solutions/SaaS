<template>
  <div>
    <h1 class="text-2xl font-bold text-white mb-6">
      Audit Log
    </h1>

    <div class="flex flex-wrap gap-3 mb-4">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search..."
        class="rounded-md border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none"
        @input="debouncedFetch"
      >
      <select
        v-model="filters.event"
        class="rounded-md border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none"
        @change="fetchLogs(1)"
      >
        <option value="">
          All Events
        </option>
        <option value="created">
          Created
        </option>
        <option value="updated">
          Updated
        </option>
        <option value="deleted">
          Deleted
        </option>
      </select>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-700">
        <thead class="bg-gray-900/50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
              Time
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
              Event
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
              Subject
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
              Causer
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">
              Description
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr v-if="isLoading">
            <td
              colspan="5"
              class="px-6 py-8 text-center text-sm text-gray-400"
            >
              Loading...
            </td>
          </tr>
          <tr v-else-if="logs.length === 0">
            <td
              colspan="5"
              class="px-6 py-8 text-center text-sm text-gray-400"
            >
              No audit entries.
            </td>
          </tr>
          <tr
            v-for="log in logs"
            :key="log.id"
            class="hover:bg-gray-700/50"
          >
            <td class="px-6 py-4 text-sm text-gray-400 whitespace-nowrap">
              {{ formatDate(log.created_at) }}
            </td>
            <td class="px-6 py-4">
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="eventClass(log.event)"
              >{{ log.event }}</span>
            </td>
            <td class="px-6 py-4 text-sm text-white">
              {{ log.subject_type?.split('\\').pop() }} #{{ log.subject_id }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-300">
              {{ log.causer?.name ?? 'System' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-400">
              {{ log.description }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="pagination.lastPage > 1"
      class="flex items-center justify-between mt-4"
    >
      <span class="text-sm text-gray-400">Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
      <div class="flex gap-2">
        <button
          :disabled="pagination.currentPage <= 1"
          class="px-3 py-1 text-sm border border-gray-600 rounded-md text-gray-300 disabled:opacity-50"
          @click="fetchLogs(pagination.currentPage - 1)"
        >
          Previous
        </button>
        <button
          :disabled="pagination.currentPage >= pagination.lastPage"
          class="px-3 py-1 text-sm border border-gray-600 rounded-md text-gray-300 disabled:opacity-50"
          @click="fetchLogs(pagination.currentPage + 1)"
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
import apiClient from '@/api/client';

interface AuditLog {
    id: number;
    event: string;
    subject_type: string;
    subject_id: number;
    description: string;
    causer?: { name: string };
    created_at: string;
}

const isLoading = ref(true);
const logs = ref<AuditLog[]>([]);
const filters = reactive({ search: '', event: '' });
const pagination = reactive({ currentPage: 1, lastPage: 1 });

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function eventClass(event: string): string {
    const map: Record<string, string> = {
        created: 'bg-green-900/50 text-green-400',
        updated: 'bg-blue-900/50 text-blue-400',
        deleted: 'bg-red-900/50 text-red-400',
    };
    return map[event] ?? 'bg-gray-700 text-gray-300';
}

async function fetchLogs(page = 1): Promise<void> {
    isLoading.value = true;
    try {
        const params: Record<string, unknown> = { page };
        if (filters.search) params.search = filters.search;
        if (filters.event) params.event = filters.event;
        const { data } = await apiClient.get('/admin/audit', { params });
        logs.value = data.data;
        pagination.currentPage = data.current_page;
        pagination.lastPage = data.last_page;
    } finally {
        isLoading.value = false;
    }
}

const debouncedFetch = useDebounceFn(() => fetchLogs(1), 300);

onMounted(() => fetchLogs());
</script>
