import { onMounted, onUnmounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import {
    subscribeTenantChannel,
    subscribeTenantPresence,
    subscribeUserChannel,
    leaveTenantChannel,
    leaveTenantPresence,
    leaveUserChannel,
} from '@/realtime/channels';

interface OnlineMember {
    id: number;
    name: string;
}

/**
 * Composable for real-time tenant channel subscriptions.
 *
 * Subscribes to:
 * - Private tenant channel for entity updates
 * - Presence channel for online indicators
 * - User channel for personal notifications
 */
export function useRealtime() {
    const authStore = useAuthStore();
    const tenantStore = useTenantStore();
    const notifications = useNotificationStore();

    const onlineMembers = ref<OnlineMember[]>([]);
    let tenantId: number | null = null;
    let userId: number | null = null;

    function connect(): void {
        tenantId = tenantStore.currentId;
        userId = authStore.user?.id ?? null;

        if (tenantId) {
            // Tenant channel — entity updates
            subscribeTenantChannel(tenantId)
                .listen('.CustomerUpdated', (data: { customer: { id: number; name: string } }) => {
                    notifications.info(`Customer "${data.customer.name}" was updated.`);
                })
                .listen('.ImportCompleted', (data: { import: { id: number; status: string } }) => {
                    notifications.success(`Import #${data.import.id} ${data.import.status}.`);
                });

            // Presence channel — online members
            const presence = subscribeTenantPresence(tenantId);
            presence
                .here((members: OnlineMember[]) => {
                    onlineMembers.value = members;
                })
                .joining((member: OnlineMember) => {
                    onlineMembers.value.push(member);
                })
                .leaving((member: OnlineMember) => {
                    onlineMembers.value = onlineMembers.value.filter(m => m.id !== member.id);
                });
        }

        if (userId) {
            // User channel — personal notifications
            subscribeUserChannel(userId)
                .listen('.UserNotification', (data: { type?: string; message?: string }) => {
                    notifications.info(data.message ?? 'You have a new notification.');
                });
        }
    }

    function disconnect(): void {
        if (tenantId) {
            leaveTenantChannel(tenantId);
            leaveTenantPresence(tenantId);
        }
        if (userId) {
            leaveUserChannel(userId);
        }
    }

    onMounted(() => connect());
    onUnmounted(() => disconnect());

    return {
        onlineMembers,
    };
}
