<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Files</h1>
            <label
                v-if="tenantStore.canWrite"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-900 text-white rounded-md hover:bg-gray-800 cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Upload
                <input type="file" class="hidden" @change="uploadFile" />
            </label>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div v-if="isLoading" class="p-6 text-center text-sm text-gray-500">Loading...</div>
            <div v-else-if="files.length === 0" class="p-6 text-center text-sm text-gray-500">
                No files uploaded yet.
            </div>

            <table v-else class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Size
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Uploaded
                        </th>
                        <th class="px-6 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="file in files" :key="file.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ file.name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ file.mime_type }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatFileSize(file.size) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(file.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button
                                class="text-sm text-indigo-600 hover:text-indigo-800"
                                @click="downloadFile(file.id)"
                            >
                                Download
                            </button>
                            <button
                                v-if="tenantStore.canWrite"
                                class="text-sm text-red-600 hover:text-red-800"
                                @click="deleteFile(file.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                v-if="pagination.lastPage > 1"
                class="px-4 py-3 border-t border-gray-200 flex items-center justify-between"
            >
                <span class="text-xs text-gray-500"
                    >Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span
                >
                <div class="flex gap-2">
                    <button
                        :disabled="pagination.currentPage <= 1"
                        class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
                        @click="loadFiles(pagination.currentPage - 1)"
                    >
                        Previous
                    </button>
                    <button
                        :disabled="pagination.currentPage >= pagination.lastPage"
                        class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
                        @click="loadFiles(pagination.currentPage + 1)"
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
import { fileApi } from '@/api';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import type { File as AppFile } from '@/api/types';

const tenantStore = useTenantStore();
const notifications = useNotificationStore();

const files = ref<AppFile[]>([]);
const isLoading = ref(true);
const pagination = reactive({ currentPage: 1, lastPage: 1 });

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

async function loadFiles(page = 1): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await fileApi.list({ page });
        files.value = data.data;
        pagination.currentPage = data.current_page;
        pagination.lastPage = data.last_page;
    } finally {
        isLoading.value = false;
    }
}

async function uploadFile(event: Event): Promise<void> {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    try {
        const { data } = await fileApi.upload(formData);
        files.value.unshift(data.data);
        notifications.success('File uploaded.');
    } catch {
        notifications.error('Failed to upload file.');
    }
    target.value = '';
}

async function downloadFile(fileId: number): Promise<void> {
    try {
        const { data } = await fileApi.download(fileId);
        const url = URL.createObjectURL(data);
        const a = document.createElement('a');
        a.href = url;
        a.download = '';
        a.click();
        URL.revokeObjectURL(url);
    } catch {
        notifications.error('Failed to download file.');
    }
}

async function deleteFile(fileId: number): Promise<void> {
    try {
        await fileApi.delete(fileId);
        files.value = files.value.filter((f) => f.id !== fileId);
        notifications.success('File deleted.');
    } catch {
        notifications.error('Failed to delete file.');
    }
}

onMounted(() => loadFiles());
</script>
