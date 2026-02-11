<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        Imports
      </h1>
      <label
        v-if="tenantStore.canWrite"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-900 text-white rounded-md hover:bg-gray-800 cursor-pointer"
      >
        <svg
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
          />
        </svg>
        New Import
        <input
          type="file"
          accept=".csv,.xlsx"
          class="hidden"
          @change="startImport"
        >
      </label>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div
        v-if="isLoading"
        class="p-6 text-center text-sm text-gray-500"
      >
        Loading...
      </div>
      <div
        v-else-if="imports.length === 0"
        class="p-6 text-center text-sm text-gray-500"
      >
        No imports yet.
      </div>

      <table
        v-else
        class="min-w-full divide-y divide-gray-200"
      >
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              File
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Type
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Progress
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Started
            </th>
            <th class="px-6 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="imp in imports"
            :key="imp.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
              {{ imp.file_name }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500 capitalize">
              {{ imp.entity_type }}
            </td>
            <td class="px-6 py-4">
              <span
                :class="statusClass(imp.status)"
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              >
                {{ imp.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <template v-if="imp.total_rows">
                {{ imp.processed_rows ?? 0 }} / {{ imp.total_rows }}
                <span
                  v-if="imp.failed_rows"
                  class="text-red-500"
                >({{ imp.failed_rows }} failed)</span>
              </template>
              <template v-else>
                —
              </template>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ formatDate(imp.created_at) }}
            </td>
            <td class="px-6 py-4 text-right">
              <button
                v-if="imp.status === 'failed'"
                class="text-sm text-indigo-600 hover:text-indigo-800"
                @click="retryImport(imp.id)"
              >
                Retry
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="pagination.lastPage > 1"
        class="px-4 py-3 border-t border-gray-200 flex items-center justify-between"
      >
        <span class="text-xs text-gray-500">Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
        <div class="flex gap-2">
          <button
            :disabled="pagination.currentPage <= 1"
            class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            @click="loadImports(pagination.currentPage - 1)"
          >
            Previous
          </button>
          <button
            :disabled="pagination.currentPage >= pagination.lastPage"
            class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            @click="loadImports(pagination.currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { importApi } from '@/api';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import type { Import } from '@/api/types';

const tenantStore = useTenantStore();
const notifications = useNotificationStore();

const imports = ref<Import[]>([]);
const isLoading = ref(true);
const pagination = reactive({ currentPage: 1, lastPage: 1 });

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
}

async function loadImports(page = 1): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await importApi.list({ page });
        imports.value = data.data;
        pagination.currentPage = data.current_page;
        pagination.lastPage = data.last_page;
    } finally {
        isLoading.value = false;
    }
}

async function startImport(event: Event): Promise<void> {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('entity_type', 'customer');

    try {
        const { data } = await importApi.create(formData);
        imports.value.unshift(data.data);
        notifications.success('Import started.');
    } catch {
        notifications.error('Failed to start import.');
    }
    target.value = '';
}

async function retryImport(id: number): Promise<void> {
    try {
        await importApi.retry(id);
        const imp = imports.value.find((i) => i.id === id);
        if (imp) imp.status = 'pending';
        notifications.success('Import retrying.');
    } catch {
        notifications.error('Failed to retry import.');
    }
}

onMounted(() => loadImports());
</script>
