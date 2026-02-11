<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">
      Dashboard
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-sm text-gray-500">
          Workspace
        </div>
        <div class="text-lg font-semibold text-gray-900 mt-1">
          {{ tenantStore.current?.name ?? '—' }}
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-sm text-gray-500">
          Your role
        </div>
        <div class="text-lg font-semibold text-gray-900 mt-1 capitalize">
          {{ tenantStore.currentRole ?? '—' }}
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-sm text-gray-500">
          Status
        </div>
        <div class="text-lg font-semibold text-green-600 mt-1 capitalize">
          {{ tenantStore.current?.status ?? '—' }}
        </div>
      </div>
    </div>

    <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">
        Quick actions
      </h2>
      <div class="flex gap-3">
        <router-link
          to="/customers"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-900 text-white rounded-md hover:bg-gray-800"
        >
          <Users class="h-4 w-4" />
          View Customers
        </router-link>
        <router-link
          to="/customers/create"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
        >
          <Plus class="h-4 w-4" />
          Add Customer
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Users, Plus } from 'lucide-vue-next';
import { useTenantStore } from '@/stores/tenant';

const tenantStore = useTenantStore();
</script>
