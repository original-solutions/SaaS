import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import type { TenantRole } from '@/api/types';

/**
 * Composable exposing permission checks for the current user + tenant.
 */
export function usePermissions() {
  const auth = useAuthStore();
  const tenant = useTenantStore();

  const role = computed<TenantRole | null>(() => tenant.currentRole);
  const isSuperAdmin = computed(() => auth.isSuperAdmin);
  const isOwner = computed(() => tenant.isOwner);
  const isAdmin = computed(() => tenant.isAdmin);
  const canWrite = computed(() => tenant.canWrite);

  function hasRole(roles: TenantRole | TenantRole[]): boolean {
    const arr = Array.isArray(roles) ? roles : [roles];
    return role.value !== null && arr.includes(role.value);
  }

  return { role, isSuperAdmin, isOwner, isAdmin, canWrite, hasRole };
}
