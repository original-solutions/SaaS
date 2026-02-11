<template>
  <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm">
    <TransitionGroup name="toast">
      <div
        v-for="notification in store.notifications"
        :key="notification.id"
        :class="[
          'px-4 py-3 rounded-lg shadow-lg text-sm flex items-center justify-between gap-3',
          typeClasses[notification.type],
        ]"
      >
        <span>{{ notification.message }}</span>
        <button
          class="opacity-60 hover:opacity-100 shrink-0"
          @click="store.remove(notification.id)"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { useNotificationStore } from '@/stores/notification';

const store = useNotificationStore();

const typeClasses: Record<string, string> = {
    success: 'bg-green-50 text-green-800 border border-green-200',
    error: 'bg-red-50 text-red-800 border border-red-200',
    warning: 'bg-yellow-50 text-yellow-800 border border-yellow-200',
    info: 'bg-blue-50 text-blue-800 border border-blue-200',
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
