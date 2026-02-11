<template>
  <div ref="switcherRef" class="relative">
    <button
      class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-md px-3 py-1.5"
      @click="open = !open"
    >
      <Building2 class="h-4 w-4" />
      <span>{{ tenantStore.current?.name ?? 'Select workspace' }}</span>
      <ChevronDown class="h-3 w-3" />
    </button>

    <div
      v-if="open"
      class="absolute left-0 mt-1 w-56 bg-white rounded-md shadow-lg border border-gray-200 py-1 z-50"
    >
      <button
        v-for="tenant in tenantStore.all"
        :key="tenant.id"
        :class="[
          'w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center justify-between',
          tenant.id === tenantStore.currentId ? 'text-gray-900 font-medium' : 'text-gray-600',
        ]"
        @click="switchTenant(tenant)"
      >
        <span>{{ tenant.name }}</span>
        <Check v-if="tenant.id === tenantStore.currentId" class="h-4 w-4 text-gray-600" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { Building2, ChevronDown, Check } from 'lucide-vue-next';
import { useTenantStore } from '@/stores/tenant';
import type { Tenant } from '@/api/types';

const tenantStore = useTenantStore();
const open = ref(false);
const switcherRef = ref<HTMLElement | null>(null);

onClickOutside(switcherRef, () => {
  open.value = false;
});

function switchTenant(tenant: Tenant): void {
  tenantStore.setCurrent(tenant);
  open.value = false;
  // Reload current page to fetch data for new tenant
  window.location.reload();
}
</script>
