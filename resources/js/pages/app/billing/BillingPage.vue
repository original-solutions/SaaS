<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Billing</h1>

    <!-- Read-only mode banner -->
    <div v-if="isReadOnly" class="mb-6 rounded-md bg-amber-50 border border-amber-200 p-4">
      <div class="flex items-center gap-3">
        <svg
          class="h-5 w-5 text-amber-500 flex-shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.999L13.732 4.001c-.77-1.333-2.694-1.333-3.464 0L3.34 16.001c-.77 1.332.192 2.999 1.732 2.999z"
          />
        </svg>
        <div>
          <p class="text-sm font-medium text-amber-800">Your subscription has expired</p>
          <p class="text-xs text-amber-700 mt-0.5">Upgrade your plan to regain full access.</p>
        </div>
        <router-link
          to="/billing/plans"
          class="ml-auto px-3 py-1.5 text-xs font-medium bg-amber-600 text-white rounded-md hover:bg-amber-700"
        >
          Upgrade
        </router-link>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Current Plan -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Plan</h2>
        <div v-if="isLoadingSub" class="text-sm text-gray-500">Loading...</div>
        <template v-else-if="subscription">
          <div class="flex items-center justify-between mb-4">
            <div>
              <p class="text-xl font-bold text-gray-900">
                {{ subscription.plan?.name ?? 'Plan' }}
              </p>
              <p class="text-sm text-gray-500 capitalize">
                {{ subscription.status }}
              </p>
            </div>
            <span
              :class="statusBadgeClass(subscription.status)"
              class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
            >
              {{ subscription.status }}
            </span>
          </div>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">Period</dt>
              <dd class="text-gray-900">
                {{ formatDate(subscription.current_period_start) }} —
                {{ formatDate(subscription.current_period_end) }}
              </dd>
            </div>
            <div v-if="subscription.grace_period_end" class="flex justify-between">
              <dt class="text-gray-500">Grace period ends</dt>
              <dd class="text-red-600">
                {{ formatDate(subscription.grace_period_end) }}
              </dd>
            </div>
          </dl>
        </template>
        <div v-else class="text-sm text-gray-500">
          <p>No active subscription.</p>
          <router-link
            to="/billing/plans"
            class="text-indigo-600 hover:text-indigo-800 font-medium"
          >
            View available plans &rarr;
          </router-link>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Billing Actions</h2>
        <div class="space-y-3">
          <router-link
            to="/billing/plans"
            class="flex items-center justify-between py-2 text-sm text-gray-700 hover:text-gray-900"
          >
            <span>View Plans</span>
            <span class="text-gray-400">&rarr;</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { billingApi } from '@/api';
import type { Subscription } from '@/api/types';

const subscription = ref<Subscription | null>(null);
const isLoadingSub = ref(true);
const isReadOnly = computed(() => {
  if (!subscription.value) return false;
  return subscription.value.status === 'cancelled' || subscription.value.status === 'past_due';
});

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function statusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    trialing: 'bg-blue-100 text-blue-800',
    past_due: 'bg-amber-100 text-amber-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return map[status] ?? 'bg-gray-100 text-gray-800';
}

async function loadBilling(): Promise<void> {
  try {
    const { data } = await billingApi.current();
    subscription.value = (data as { data: Subscription }).data;
  } catch {
    subscription.value = null;
  } finally {
    isLoadingSub.value = false;
  }
}

onMounted(loadBilling);
</script>
