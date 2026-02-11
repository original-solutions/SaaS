<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Team Settings</h1>

        <!-- Tenant Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                {{ tenantStore.current?.name }}
            </h2>
            <p class="text-sm text-gray-500">Slug: {{ tenantStore.current?.slug }}</p>
        </div>

        <!-- Members -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Members</h2>
            </div>
            <div v-if="membersLoading" class="text-sm text-gray-400">Loading...</div>
            <table v-else class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                            Name
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                            Email
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                            Role
                        </th>
                        <th v-if="tenantStore.isOwner" class="px-4 py-2" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="m in members" :key="m.id">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ m.name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ m.email }}
                        </td>
                        <td class="px-4 py-3">
                            <select
                                v-if="tenantStore.isOwner && m.id !== authStore.user?.id"
                                v-model="m.role"
                                class="text-xs border-gray-300 rounded-md"
                                @change="updateRole(m)"
                            >
                                <option value="owner">Owner</option>
                                <option value="member">Member</option>
                                <option value="readonly">Read Only</option>
                            </select>
                            <span v-else class="text-xs text-gray-500">{{ m.role }}</span>
                        </td>
                        <td v-if="tenantStore.isOwner" class="px-4 py-3 text-right">
                            <button
                                v-if="m.id !== authStore.user?.id"
                                class="text-xs text-red-600 hover:text-red-700"
                                @click="removeMember(m)"
                            >
                                Remove
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Invite Member -->
        <div
            v-if="tenantStore.isOwner"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6"
        >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invite Member</h2>
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="sendInvite">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        v-model="inviteForm.email"
                        type="email"
                        required
                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select
                        v-model="inviteForm.role"
                        class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="member">Member</option>
                        <option value="readonly">Read Only</option>
                    </select>
                </div>
                <button
                    type="submit"
                    :disabled="inviteSending"
                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ inviteSending ? 'Sending...' : 'Send Invite' }}
                </button>
            </form>
        </div>

        <!-- Pending Invitations -->
        <div
            v-if="tenantStore.isOwner && pendingInvites.length > 0"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
        >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Pending Invitations</h2>
            <div
                v-for="inv in pendingInvites"
                :key="inv.id"
                class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
            >
                <div>
                    <p class="text-sm text-gray-900">
                        {{ inv.email }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ inv.role }} &middot; expires {{ formatDate(inv.expires_at) }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        class="text-xs text-indigo-600 hover:text-indigo-700"
                        @click="resendInvite(inv)"
                    >
                        Resend
                    </button>
                    <button
                        class="text-xs text-red-600 hover:text-red-700"
                        @click="cancelInvite(inv)"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import apiClient from '@/api/client';

const tenantStore = useTenantStore();
const authStore = useAuthStore();
const notifications = useNotificationStore();

interface Member {
    id: number;
    name: string;
    email: string;
    role: string;
}
interface Invite {
    id: number;
    email: string;
    role: string;
    expires_at: string;
}

const members = ref<Member[]>([]);
const membersLoading = ref(true);
const pendingInvites = ref<Invite[]>([]);
const inviteForm = reactive({ email: '', role: 'member' });
const inviteSending = ref(false);

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

async function fetchMembers(): Promise<void> {
    membersLoading.value = true;
    try {
        const { data } = await apiClient.get('/members');
        members.value = data.data ?? data;
    } finally {
        membersLoading.value = false;
    }
}

async function fetchInvites(): Promise<void> {
    try {
        const { data } = await apiClient.get('/invitations');
        pendingInvites.value = (data.data ?? data).filter(
            (i: Invite & { accepted_at?: string }) => !i.accepted_at,
        );
    } catch {
        /* may not have permission */
    }
}

async function updateRole(m: Member): Promise<void> {
    try {
        await apiClient.put(`/members/${m.id}`, { role: m.role });
        notifications.success(`${m.name}'s role updated.`);
    } catch {
        notifications.error('Failed to update role.');
    }
}

async function removeMember(m: Member): Promise<void> {
    if (!confirm(`Remove ${m.name} from the team?`)) return;
    try {
        await apiClient.delete(`/members/${m.id}`);
        members.value = members.value.filter((x) => x.id !== m.id);
        notifications.success(`${m.name} removed.`);
    } catch {
        notifications.error('Failed to remove member.');
    }
}

async function sendInvite(): Promise<void> {
    inviteSending.value = true;
    try {
        const { data } = await apiClient.post('/invitations', inviteForm);
        pendingInvites.value.push(data.data ?? data);
        inviteForm.email = '';
        inviteForm.role = 'member';
        notifications.success('Invitation sent.');
    } catch {
        notifications.error('Failed to send invitation.');
    } finally {
        inviteSending.value = false;
    }
}

async function resendInvite(inv: Invite): Promise<void> {
    try {
        await apiClient.post(`/invitations/${inv.id}/resend`);
        notifications.success('Invitation resent.');
    } catch {
        notifications.error('Failed to resend.');
    }
}

async function cancelInvite(inv: Invite): Promise<void> {
    try {
        await apiClient.delete(`/invitations/${inv.id}`);
        pendingInvites.value = pendingInvites.value.filter((i) => i.id !== inv.id);
        notifications.success('Invitation cancelled.');
    } catch {
        notifications.error('Failed to cancel invitation.');
    }
}

onMounted(() => {
    fetchMembers();
    if (tenantStore.isOwner) fetchInvites();
});
</script>
