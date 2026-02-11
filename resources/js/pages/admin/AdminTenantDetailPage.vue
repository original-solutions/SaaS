<template>
  <div>
    <div class="flex items-center gap-3 mb-6">
      <router-link
        to="/admin/tenants"
        class="text-gray-400 hover:text-gray-300"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
      </router-link>
      <h1 class="text-2xl font-bold text-white">
        {{ tenant?.name ?? 'Tenant' }}
      </h1>
      <span
        v-if="tenant"
        :class="statusClass(tenant.status)"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
      >{{ tenant.status }}</span>
    </div>

    <div
      v-if="isLoading"
      class="text-sm text-gray-400"
    >
      Loading...
    </div>

    <template v-else-if="tenant">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Info -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
              Details
            </h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <dt class="text-gray-400">
                  Name
                </dt>
                <dd class="text-white">
                  {{ tenant.name }}
                </dd>
              </div>
              <div>
                <dt class="text-gray-400">
                  Slug
                </dt>
                <dd class="text-white font-mono">
                  {{ tenant.slug }}
                </dd>
              </div>
              <div>
                <dt class="text-gray-400">
                  Created
                </dt>
                <dd class="text-white">
                  {{ formatDate(tenant.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-gray-400">
                  Plan
                </dt>
                <dd class="text-white">
                  {{ tenant.plan ?? 'None' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Members -->
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
              Members
            </h2>
            <div
              v-if="members.length === 0"
              class="text-sm text-gray-400"
            >
              No members.
            </div>
            <ul
              v-else
              class="space-y-2"
            >
              <li
                v-for="member in members"
                :key="member.id"
                class="flex items-center justify-between py-2"
              >
                <div>
                  <p class="text-sm text-white">
                    {{ member.name }}
                  </p>
                  <p class="text-xs text-gray-400">
                    {{ member.email }}
                  </p>
                </div>
                <router-link
                  :to="`/admin/users/${member.id}`"
                  class="text-xs text-indigo-400 hover:text-indigo-300"
                >
                  View
                </router-link>
              </li>
            </ul>
          </div>
        </div>

        <!-- Usage Stats Sidebar -->
        <div class="space-y-6">
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
              Usage Stats
            </h2>
            <dl class="space-y-3 text-sm">
              <div class="flex justify-between">
                <dt class="text-gray-400">
                  Users
                </dt>
                <dd class="text-white font-bold">
                  {{ tenant.users_count ?? 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">
                  Customers
                </dt>
                <dd class="text-white font-bold">
                  {{ tenant.customers_count ?? 0 }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Actions -->
          <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
              Actions
            </h2>
            <div class="space-y-2">
              <button
                v-if="tenant.disabled_at"
                class="w-full px-3 py-2 text-sm text-green-400 border border-green-500/30 rounded-md hover:bg-green-500/10"
                @click="enableTenant"
              >
                Enable
              </button>
              <button
                v-else
                class="w-full px-3 py-2 text-sm text-red-400 border border-red-500/30 rounded-md hover:bg-red-500/10"
                @click="disableTenant"
              >
                Disable
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { adminTenantsApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import type { AdminTenantDetail, User } from '@/api/types';

const route = useRoute();
const notifications = useNotificationStore();
const tenantId = Number(route.params.id);

const tenant = ref<AdminTenantDetail | null>(null);
const members = ref<User[]>([]);
const isLoading = ref(true);

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        active: 'bg-green-500/20 text-green-400',
        inactive: 'bg-gray-500/20 text-gray-400',
        suspended: 'bg-red-500/20 text-red-400',
    };
    return map[status] ?? 'bg-gray-500/20 text-gray-400';
}

async function loadTenant(): Promise<void> {
    try {
        const [tenantRes, membersRes] = await Promise.all([
            adminTenantsApi.show(tenantId),
            adminTenantsApi.members(tenantId),
        ]);
        tenant.value = tenantRes.data.data;
        members.value = membersRes.data.data;
    } finally {
        isLoading.value = false;
    }
}

async function disableTenant(): Promise<void> {
    try {
        await adminTenantsApi.disable(tenantId);
        if (tenant.value) {
            tenant.value.disabled_at = new Date().toISOString();
            tenant.value.status = 'suspended';
        }
        notifications.success('Tenant disabled.');
    } catch {
        notifications.error('Failed to disable tenant.');
    }
}

async function enableTenant(): Promise<void> {
    try {
        await adminTenantsApi.enable(tenantId);
        if (tenant.value) {
            tenant.value.disabled_at = null;
            tenant.value.status = 'active';
        }
        notifications.success('Tenant enabled.');
    } catch {
        notifications.error('Failed to enable tenant.');
    }
}

onMounted(loadTenant);
</script>
