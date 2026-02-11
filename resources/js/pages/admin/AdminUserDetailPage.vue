<template>
  <div>
    <router-link
      to="/admin/users"
      class="text-sm text-gray-400 hover:text-gray-300 mb-4 inline-block"
    >
      &larr; Back to users
    </router-link>

    <div
      v-if="isLoading"
      class="text-gray-400 py-8 text-center"
    >
      Loading...
    </div>

    <template v-else-if="user">
      <div class="flex items-start justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-white">
            {{ user.name }}
          </h1>
          <p class="text-sm text-gray-400">
            {{ user.email }}
          </p>
        </div>
        <div class="flex gap-2">
          <button
            v-if="user.locked_at"
            class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded-md"
            @click="unlock"
          >
            Unlock
          </button>
          <button
            v-else
            class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-md"
            @click="lock"
          >
            Lock
          </button>
          <button
            class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-md"
            @click="impersonate"
          >
            Impersonate
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Details -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
              Details
            </h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <dt class="text-gray-400">
                  Status
                </dt>
                <dd class="text-white mt-1">
                  <span
                    v-if="user.locked_at"
                    class="text-red-400"
                  >Locked since {{ formatDate(user.locked_at) }}</span>
                  <span
                    v-else
                    class="text-green-400"
                  >Active</span>
                </dd>
              </div>
              <div>
                <dt class="text-gray-400">
                  Super Admin
                </dt>
                <dd class="text-white mt-1">
                  {{ user.is_super_admin ? 'Yes' : 'No' }}
                </dd>
              </div>
              <div>
                <dt class="text-gray-400">
                  Created
                </dt>
                <dd class="text-white mt-1">
                  {{ formatDate(user.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-gray-400">
                  Email Verified
                </dt>
                <dd class="text-white mt-1">
                  {{
                    user.email_verified_at
                      ? formatDate(user.email_verified_at)
                      : 'No'
                  }}
                </dd>
              </div>
              <div>
                <dt class="text-gray-400">
                  Two-Factor
                </dt>
                <dd class="text-white mt-1">
                  {{ user.two_factor_confirmed_at ? 'Enabled' : 'Disabled' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Memberships -->
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
              Tenant Memberships
            </h2>
            <div
              v-if="user.memberships && user.memberships.length > 0"
              class="space-y-2"
            >
              <div
                v-for="m in user.memberships"
                :key="m.tenant_id"
                class="flex items-center justify-between py-2 border-b border-gray-700 last:border-0"
              >
                <div>
                  <router-link
                    :to="`/admin/tenants/${m.tenant_id}`"
                    class="text-sm text-white hover:underline"
                  >
                    {{ m.tenant_name }}
                  </router-link>
                  <span class="ml-2 text-xs text-gray-400">{{ m.role }}</span>
                </div>
              </div>
            </div>
            <p
              v-else
              class="text-sm text-gray-400"
            >
              No memberships.
            </p>
          </div>

          <!-- Login History -->
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
              Login History
            </h2>
            <div
              v-if="user.login_events && user.login_events.length > 0"
              class="space-y-2"
            >
              <div
                v-for="e in user.login_events"
                :key="e.id"
                class="flex items-center justify-between py-2 border-b border-gray-700 last:border-0"
              >
                <div>
                  <span class="text-sm text-white">{{ e.event_type }}</span>
                  <span class="ml-2 text-xs text-gray-400">{{
                    e.ip_address
                  }}</span>
                </div>
                <span class="text-xs text-gray-400">{{
                  formatDate(e.created_at)
                }}</span>
              </div>
            </div>
            <p
              v-else
              class="text-sm text-gray-400"
            >
              No login events recorded.
            </p>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-sm font-medium text-gray-400 mb-3">
              Quick Stats
            </h3>
            <dl class="space-y-3 text-sm">
              <div class="flex justify-between">
                <dt class="text-gray-400">
                  Tenants
                </dt>
                <dd class="text-white font-medium">
                  {{ user.tenants_count ?? 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">
                  Sessions
                </dt>
                <dd class="text-white font-medium">
                  {{ user.sessions_count ?? 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">
                  Login Events
                </dt>
                <dd class="text-white font-medium">
                  {{ user.login_events?.length ?? 0 }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { adminUsersApi } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import type { AdminUserDetail } from '@/api/types';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const notifications = useNotificationStore();

const user = ref<AdminUserDetail | null>(null);
const isLoading = ref(true);

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

async function fetchUser(): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await adminUsersApi.show(Number(route.params.id));
        user.value = data.data;
    } finally {
        isLoading.value = false;
    }
}

async function lock(): Promise<void> {
    if (!user.value) return;
    try {
        await adminUsersApi.lock(user.value.id);
        user.value.locked_at = new Date().toISOString();
        notifications.success('User locked.');
    } catch {
        notifications.error('Failed to lock user.');
    }
}

async function unlock(): Promise<void> {
    if (!user.value) return;
    try {
        await adminUsersApi.unlock(user.value.id);
        user.value.locked_at = null;
        notifications.success('User unlocked.');
    } catch {
        notifications.error('Failed to unlock user.');
    }
}

async function impersonate(): Promise<void> {
    if (!user.value) return;
    try {
        const { data } = await adminUsersApi.impersonate(user.value.id);
        const payload = data.data;
        authStore.startImpersonation(payload.access_token, payload.expires_at);
        await authStore.fetchUser();
        notifications.info(`Now impersonating ${user.value.name}.`);
        router.push('/');
    } catch {
        notifications.error('Failed to impersonate.');
    }
}

onMounted(() => fetchUser());
</script>
