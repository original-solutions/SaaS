import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export interface Notification {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    timeout?: number;
}

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref<Notification[]>([]);

    const latest = computed(() => notifications.value[notifications.value.length - 1] ?? null);

    function add(notification: Omit<Notification, 'id'>): void {
        const id = crypto.randomUUID();
        notifications.value.push({ ...notification, id });

        const timeout = notification.timeout ?? 5000;
        if (timeout > 0) {
            setTimeout(() => remove(id), timeout);
        }
    }

    function success(message: string): void {
        add({ type: 'success', message });
    }

    function error(message: string): void {
        add({ type: 'error', message, timeout: 8000 });
    }

    function warning(message: string): void {
        add({ type: 'warning', message });
    }

    function info(message: string): void {
        add({ type: 'info', message });
    }

    function remove(id: string): void {
        notifications.value = notifications.value.filter((n) => n.id !== id);
    }

    function clear(): void {
        notifications.value = [];
    }

    return {
        notifications,
        latest,
        add,
        success,
        error,
        warning,
        info,
        remove,
        clear,
    };
});
