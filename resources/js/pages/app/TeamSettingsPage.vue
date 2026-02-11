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
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
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
              <BaseSelect
                v-if="tenantStore.isOwner && m.id !== authStore.user?.id"
                v-model="m.role"
                :options="memberRoleOptions"
                class="w-[140px]"
                trigger-class="text-xs"
                @update:model-value="updateRole(m)"
              />
              <span v-else class="text-xs text-gray-500">{{ m.role }}</span>
            </td>
            <td v-if="tenantStore.isOwner" class="px-4 py-3 text-right">
              <BaseButton
                v-if="m.id !== authStore.user?.id"
                variant="ghost"
                size="sm"
                class="text-xs text-red-600 hover:text-red-700"
                @click="removeMember(m)"
              >
                Remove
              </BaseButton>
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
        <BaseInput
          v-model="inviteEmail"
          v-bind="inviteEmailAttrs"
          label="Email"
          type="email"
          class="flex-1 min-w-[200px]"
          :error="inviteErrors.email"
        />
        <BaseSelect
          v-model="inviteRole"
          v-bind="inviteRoleAttrs"
          label="Role"
          :options="inviteRoleOptions"
          :error="inviteErrors.role"
          class="w-[160px]"
        />
        <BaseButton type="submit" variant="primary" :loading="inviteSending">
          Send Invite
        </BaseButton>
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
          <BaseButton
            variant="ghost"
            size="sm"
            class="text-xs text-indigo-600 hover:text-indigo-700"
            @click="resendInvite(inv)"
          >
            Resend
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            class="text-xs text-red-600 hover:text-red-700"
            @click="cancelInvite(inv)"
          >
            Cancel
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import { confirmDialog } from '@/composables/useConfirmDialog';
import apiClient from '@/api/client';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { inviteMemberSchema } from '@/api/schemas/team';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';
import { mapLaravelErrors } from '@/lib/laravelErrors';

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

const memberRoleOptions = [
  { value: 'owner', label: 'Owner' },
  { value: 'member', label: 'Member' },
  { value: 'readonly', label: 'Read Only' },
];

const inviteRoleOptions = [
  { value: 'member', label: 'Member' },
  { value: 'readonly', label: 'Read Only' },
];

const {
  handleSubmit,
  defineField,
  errors: inviteErrors,
  setErrors: setInviteErrors,
  resetForm,
  isSubmitting: inviteSending,
} = useForm({
  validationSchema: toTypedSchema(inviteMemberSchema),
  initialValues: {
    email: '',
    role: 'member',
  },
});

const [inviteEmail, inviteEmailAttrs] = defineField('email');
const [inviteRole, inviteRoleAttrs] = defineField('role');

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
      (i: Invite & { accepted_at?: string }) => !i.accepted_at
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
  const confirmed = await confirmDialog({
    title: 'Remove member',
    description: `Remove ${m.name} from the team?`,
    confirmText: 'Remove',
    cancelText: 'Cancel',
    destructive: true,
  });

  if (!confirmed) {
    return;
  }

  try {
    await apiClient.delete(`/members/${m.id}`);
    members.value = members.value.filter((x) => x.id !== m.id);
    notifications.success(`${m.name} removed.`);
  } catch {
    notifications.error('Failed to remove member.');
  }
}

const sendInvite = handleSubmit(async (values) => {
  setInviteErrors({});
  try {
    const { data } = await apiClient.post('/invitations', values);
    pendingInvites.value.push(data.data ?? data);
    resetForm();
    notifications.success('Invitation sent.');
  } catch (err) {
    const axiosError = err as AxiosError<ApiError>;

    if (axiosError.response?.status === 422) {
      setInviteErrors(mapLaravelErrors(axiosError.response.data.errors));
      return;
    }

    notifications.error('Failed to send invitation.');
  }
});

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
