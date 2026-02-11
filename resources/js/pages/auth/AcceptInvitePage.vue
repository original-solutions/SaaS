<template>
  <GuestLayout>
    <template v-if="isLoading">
      <div class="text-center py-8">
        <div
          class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"
        />
        <p class="mt-3 text-sm text-gray-600">
          Loading invitation details...
        </p>
      </div>
    </template>

    <template v-else-if="error">
      <div class="rounded-md bg-red-50 p-4 mb-4">
        <p class="text-sm text-red-800">
          {{ error }}
        </p>
      </div>
      <div class="text-center">
        <router-link
          to="/login"
          class="text-sm text-gray-600 hover:text-gray-900"
        >
          Go to sign in
        </router-link>
      </div>
    </template>

    <template v-else-if="invitation">
      <h2 class="text-lg font-semibold text-gray-900 mb-2">
        You're Invited
      </h2>
      <p class="text-sm text-gray-600 mb-6">
        You've been invited to join a workspace as <strong>{{ invitation.role }}</strong>.
      </p>

      <!-- Email mismatch warning -->
      <div
        v-if="emailMismatch"
        class="rounded-md bg-amber-50 border border-amber-200 p-4 mb-4"
      >
        <p class="text-sm text-amber-800 mb-3">
          This invitation was sent to <strong>{{ invitation.email }}</strong>, but you're signed in as <strong>{{ auth.user?.email }}</strong>.
        </p>
        <div class="flex gap-2">
          <BaseButton
            variant="secondary"
            size="sm"
            @click="switchAccount"
          >
            Switch Account
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            @click="logoutAndContinue"
          >
            Log Out &amp; Continue
          </BaseButton>
        </div>
      </div>

      <!-- Accept invitation -->
      <template v-else>
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">
                Invited email
              </dt>
              <dd class="text-gray-900">
                {{ invitation.email }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">
                Role
              </dt>
              <dd class="text-gray-900 capitalize">
                {{ invitation.role }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">
                Expires
              </dt>
              <dd class="text-gray-900">
                {{ formatDate(invitation.expires_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <BaseButton
          variant="primary"
          :loading="isAccepting"
          class="w-full"
          @click="acceptInvitation"
        >
          Accept Invitation
        </BaseButton>
      </template>
    </template>
  </GuestLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import { invitePublicApi } from '@/api';
import GuestLayout from '@/layouts/GuestLayout.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { Invitation } from '@/api/types';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const notifications = useNotificationStore();

const invitation = ref<Invitation | null>(null);
const isLoading = ref(true);
const isAccepting = ref(false);
const error = ref('');

const token = computed(() => route.params.token as string);
const emailMismatch = computed(() => {
    if (!auth.isAuthenticated || !invitation.value) return false;
    return auth.user?.email !== invitation.value.email;
});

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

async function loadInvitation(): Promise<void> {
    try {
        const { data } = await invitePublicApi.show(token.value);
        invitation.value = data.data;
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        error.value =
            axiosError.response?.data?.message ?? 'This invitation is invalid or has expired.';
    } finally {
        isLoading.value = false;
    }
}

async function acceptInvitation(): Promise<void> {
    isAccepting.value = true;
    try {
        await invitePublicApi.accept(token.value);
        notifications.success('Invitation accepted!');
        // Refresh user tenants
        await auth.fetchUser();
        router.push('/');
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        error.value = axiosError.response?.data?.message ?? 'Failed to accept invitation.';
    } finally {
        isAccepting.value = false;
    }
}

function switchAccount(): void {
    auth.logout();
    router.push({ name: 'login', query: { redirect: route.fullPath } });
}

function logoutAndContinue(): void {
    auth.logout();
    // Redirect to login with invitation token so the user can sign in as the correct account
    router.push({
        name: 'login',
        query: { redirect: `/invitations/accept/${token.value}` },
    });
}

onMounted(loadInvitation);
</script>
