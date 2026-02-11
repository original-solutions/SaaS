import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { tenantApi } from '@/api';
import type { Tenant, TenantRole } from '@/api/types';

export const useTenantStore = defineStore('tenant', () => {
  const current = ref<Tenant | null>(null);
  const all = ref<Tenant[]>([]);
  const isLoading = ref(false);

  const currentId = computed(() => current.value?.id ?? null);
  const currentRole = computed<TenantRole | null>(() => current.value?.pivot?.role ?? null);
  const isOwner = computed(() => currentRole.value === 'owner');
  const isAdmin = computed(() => currentRole.value === 'admin' || currentRole.value === 'owner');
  const canWrite = computed(() => currentRole.value !== 'readonly');

  function setCurrent(tenant: Tenant): void {
    current.value = tenant;
    localStorage.setItem('current_tenant_id', String(tenant.id));
  }

  function setAll(tenants: Tenant[]): void {
    all.value = tenants;
  }

  async function fetchTenants(): Promise<void> {
    isLoading.value = true;
    try {
      const { data } = await tenantApi.list();
      all.value = data.data;
    } finally {
      isLoading.value = false;
    }
  }

  function initialize(tenants: Tenant[]): void {
    all.value = tenants;

    const storedId = localStorage.getItem('current_tenant_id');
    const stored = storedId ? tenants.find((t) => t.id === Number(storedId)) : null;
    current.value = stored ?? tenants[0] ?? null;

    if (current.value) {
      localStorage.setItem('current_tenant_id', String(current.value.id));
    }
  }

  function $reset(): void {
    current.value = null;
    all.value = [];
    isLoading.value = false;
  }

  return {
    current,
    all,
    isLoading,
    currentId,
    currentRole,
    isOwner,
    isAdmin,
    canWrite,
    setCurrent,
    setAll,
    fetchTenants,
    initialize,
    $reset,
  };
});
