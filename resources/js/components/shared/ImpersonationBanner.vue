<template>
    <Teleport to="body">
        <div
            v-if="authStore.isImpersonating"
            class="fixed top-0 inset-x-0 z-50 bg-amber-600 text-amber-950 text-center py-2 px-4 text-sm font-medium flex items-center justify-center gap-4"
        >
            <span>
                You are impersonating <strong>{{ authStore.user?.name }}</strong>
                <template v-if="timeRemaining"> &middot; {{ timeRemaining }} remaining</template>
            </span>
            <button
                class="px-3 py-1 bg-amber-800 text-white rounded-md text-xs hover:bg-amber-900"
                @click="stopImpersonation"
            >
                Stop Impersonating
            </button>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';

const router = useRouter();
const authStore = useAuthStore();
const notifications = useNotificationStore();
const now = ref(Date.now());

let interval: ReturnType<typeof setInterval> | null = null;

const timeRemaining = computed(() => {
    if (!authStore.impersonationExpiresAt) return '';
    const diff = new Date(authStore.impersonationExpiresAt).getTime() - now.value;
    if (diff <= 0) return 'expired';
    const minutes = Math.floor(diff / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    return `${minutes}m ${seconds}s`;
});

async function stopImpersonation(): Promise<void> {
    try {
        await authStore.stopImpersonation();
        await authStore.fetchUser();
        notifications.info('Impersonation ended.');
        router.push('/admin');
    } catch {
        notifications.error('Failed to stop impersonation.');
    }
}

onMounted(() => {
    interval = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>
