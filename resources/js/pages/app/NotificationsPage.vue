<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
      <BaseButton
        v-if="notifications.length > 0"
        variant="ghost"
        size="sm"
        :loading="markingAll"
        @click="markAllRead"
      >
        Mark all read
      </BaseButton>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="isLoading" class="p-6 text-center text-sm text-gray-500">Loading...</div>
      <div v-else-if="notifications.length === 0" class="p-6 text-center text-sm text-gray-500">
        No notifications.
      </div>

      <ul v-else class="divide-y divide-gray-200">
        <li
          v-for="notification in notifications"
          :key="notification.id"
          :class="['p-4 flex items-start gap-3', !notification.read_at ? 'bg-blue-50/50' : '']"
        >
          <div class="flex-shrink-0 mt-1">
            <div
              :class="[
                'h-2 w-2 rounded-full',
                !notification.read_at ? 'bg-blue-500' : 'bg-transparent',
              ]"
            />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-800">
              {{ notificationMessage(notification) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
              {{ formatRelative(notification.created_at) }}
            </p>
          </div>
          <BaseButton
            v-if="!notification.read_at"
            variant="ghost"
            size="sm"
            class="flex-shrink-0 text-xs text-gray-500 hover:text-gray-700"
            @click="markRead(notification.id)"
          >
            Mark read
          </BaseButton>
        </li>
      </ul>

      <div
        v-if="pagination.lastPage > 1"
        class="px-4 py-3 border-t border-gray-200 flex items-center justify-between"
      >
        <span class="text-xs text-gray-500"
          >Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span
        >
        <div class="flex gap-2">
          <BaseButton
            :disabled="pagination.currentPage <= 1"
            variant="secondary"
            size="sm"
            class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            @click="loadNotifications(pagination.currentPage - 1)"
          >
            Previous
          </BaseButton>
          <BaseButton
            :disabled="pagination.currentPage >= pagination.lastPage"
            variant="secondary"
            size="sm"
            class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            @click="loadNotifications(pagination.currentPage + 1)"
          >
            Next
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { notificationApi } from '@/api';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { Notification } from '@/api/types';

const notifications = ref<Notification[]>([]);
const isLoading = ref(true);
const markingAll = ref(false);
const pagination = reactive({ currentPage: 1, lastPage: 1 });

function formatRelative(dateStr: string): string {
  const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (seconds < 60) return 'just now';
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
}

function notificationMessage(notification: Notification): string {
  return (
    (notification.data?.message as string) ?? notification.type.split('\\').pop() ?? 'Notification'
  );
}

async function loadNotifications(page = 1): Promise<void> {
  isLoading.value = true;
  try {
    const { data } = await notificationApi.list({ page });
    notifications.value = data.data;
    pagination.currentPage = data.current_page;
    pagination.lastPage = data.last_page;
  } finally {
    isLoading.value = false;
  }
}

async function markRead(id: string): Promise<void> {
  try {
    await notificationApi.markRead(id);
    const n = notifications.value.find((n) => n.id === id);
    if (n) n.read_at = new Date().toISOString();
  } catch {
    /* silent */
  }
}

async function markAllRead(): Promise<void> {
  markingAll.value = true;
  try {
    await notificationApi.markAllRead();
    notifications.value.forEach((n) => {
      if (!n.read_at) n.read_at = new Date().toISOString();
    });
  } finally {
    markingAll.value = false;
  }
}

onMounted(() => loadNotifications());
</script>
