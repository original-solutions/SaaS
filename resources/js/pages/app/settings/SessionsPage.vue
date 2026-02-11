<template>
    <div>
        <div class="flex items-center gap-3 mb-6">
            <router-link to="/settings" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
            </router-link>
            <h1 class="text-2xl font-bold text-gray-900">Device Sessions</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">
                            Manage your active sessions across devices. If you notice any unfamiliar
                            sessions, revoke them immediately.
                        </p>
                    </div>
                    <BaseButton
                        variant="danger"
                        size="sm"
                        :loading="revokingAll"
                        @click="revokeAllSessions"
                    >
                        Revoke All
                    </BaseButton>
                </div>
            </div>

            <div v-if="isLoading" class="p-6 text-center">
                <div
                    class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"
                ></div>
            </div>

            <ul v-else class="divide-y divide-gray-200">
                <li
                    v-for="session in sessions"
                    :key="session.id"
                    class="p-4 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg
                                class="h-8 w-8 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ session.device_name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ session.ip_address }} &middot; Last active
                                {{ formatRelativeTime(session.last_active_at) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="session.revoked_at" class="text-xs text-red-500 font-medium"
                            >Revoked</span
                        >
                        <BaseButton
                            v-else
                            variant="ghost"
                            size="sm"
                            :loading="revokingId === session.id"
                            @click="revokeSession(session.id)"
                        >
                            Revoke
                        </BaseButton>
                    </div>
                </li>
                <li v-if="sessions.length === 0" class="p-6 text-center text-sm text-gray-500">
                    No active sessions found.
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { sessionApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { DeviceSession } from '@/api/types';

const notifications = useNotificationStore();

const sessions = ref<DeviceSession[]>([]);
const isLoading = ref(true);
const revokingId = ref<number | null>(null);
const revokingAll = ref(false);

function formatRelativeTime(dateStr: string): string {
    const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

async function loadSessions(): Promise<void> {
    try {
        const { data } = await sessionApi.list();
        sessions.value = data.data;
    } finally {
        isLoading.value = false;
    }
}

async function revokeSession(id: number): Promise<void> {
    revokingId.value = id;
    try {
        await sessionApi.revoke(id);
        const session = sessions.value.find((s) => s.id === id);
        if (session) session.revoked_at = new Date().toISOString();
        notifications.success('Session revoked.');
    } catch {
        notifications.error('Failed to revoke session.');
    } finally {
        revokingId.value = null;
    }
}

async function revokeAllSessions(): Promise<void> {
    revokingAll.value = true;
    try {
        await sessionApi.revokeAll();
        sessions.value.forEach((s) => {
            if (!s.revoked_at) s.revoked_at = new Date().toISOString();
        });
        notifications.success('All sessions revoked.');
    } catch {
        notifications.error('Failed to revoke sessions.');
    } finally {
        revokingAll.value = false;
    }
}

onMounted(loadSessions);
</script>
