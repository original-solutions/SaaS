import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/api';
import type { User, Tenant, LoginCredentials, LoginResult } from '@/api/types';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const tenants = ref<Tenant[]>([]);
  const isLoading = ref(false);
  const isInitialized = ref(false);

  /** Set when login returns a 2FA challenge */
  const twoFactorToken = ref<string | null>(null);
  /** Tracks if the current session is impersonated */
  const isImpersonating = ref(false);
  const impersonationExpiresAt = ref<string | null>(null);
  /** Stores original admin token while impersonating */
  const originalToken = ref<string | null>(null);

  const isAuthenticated = computed(() => !!user.value);
  const isSuperAdmin = computed(() => user.value?.is_super_admin ?? false);
  const hasTwoFactor = computed(() => !!user.value?.two_factor_secret);
  const needsTwoFactor = computed(() => !!twoFactorToken.value);

  async function login(credentials: LoginCredentials): Promise<LoginResult> {
    isLoading.value = true;
    try {
      const { data } = await authApi.login(credentials);

      if ('two_factor' in data && data.two_factor) {
        twoFactorToken.value = data.two_factor_token;
        return data;
      }

      localStorage.setItem('access_token', data.access_token);
      localStorage.setItem('refresh_token', data.refresh_token);
      user.value = data.user;
      tenants.value = data.tenants;
      return data;
    } finally {
      isLoading.value = false;
    }
  }

  async function completeTwoFactor(code: string): Promise<void> {
    if (!twoFactorToken.value) throw new Error('No 2FA challenge in progress');
    isLoading.value = true;
    try {
      const { data } = await authApi.twoFactorChallenge({
        two_factor_token: twoFactorToken.value,
        code,
      });
      if ('access_token' in data) {
        localStorage.setItem('access_token', data.access_token);
        localStorage.setItem('refresh_token', data.refresh_token);
        user.value = data.user;
        tenants.value = data.tenants;
        twoFactorToken.value = null;
      }
    } finally {
      isLoading.value = false;
    }
  }

  async function completeTwoFactorRecovery(recoveryCode: string): Promise<void> {
    if (!twoFactorToken.value) throw new Error('No 2FA challenge in progress');
    isLoading.value = true;
    try {
      const { data } = await authApi.twoFactorRecovery({
        two_factor_token: twoFactorToken.value,
        recovery_code: recoveryCode,
      });
      if ('access_token' in data) {
        localStorage.setItem('access_token', data.access_token);
        localStorage.setItem('refresh_token', data.refresh_token);
        user.value = data.user;
        tenants.value = data.tenants;
        twoFactorToken.value = null;
      }
    } finally {
      isLoading.value = false;
    }
  }

  function startImpersonation(token: string, expiresAt: string): void {
    originalToken.value = localStorage.getItem('access_token');
    localStorage.setItem('access_token', token);
    isImpersonating.value = true;
    impersonationExpiresAt.value = expiresAt;
  }

  async function stopImpersonation(): Promise<void> {
    try {
      if (!user.value) throw new Error('No user to stop impersonating');
      // Restore original admin token BEFORE calling API
      if (originalToken.value) {
        localStorage.setItem('access_token', originalToken.value);
      }
      await import('@/api').then((m) => m.adminUsersApi.stopImpersonation(user.value!.id));
    } finally {
      originalToken.value = null;
      isImpersonating.value = false;
      impersonationExpiresAt.value = null;
      await fetchUser();
    }
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout();
    } finally {
      user.value = null;
      tenants.value = [];
      localStorage.removeItem('access_token');
      localStorage.removeItem('refresh_token');
      localStorage.removeItem('current_tenant_id');
    }
  }

  async function fetchUser(): Promise<void> {
    try {
      const { data } = await authApi.me();
      user.value = data.data;
    } catch {
      user.value = null;
    }
  }

  async function initialize(): Promise<void> {
    if (isInitialized.value) return;

    const token = localStorage.getItem('access_token');
    if (token) {
      await fetchUser();
    }

    isInitialized.value = true;
  }

  function setUser(u: User): void {
    user.value = u;
  }

  function setTenants(t: Tenant[]): void {
    tenants.value = t;
  }

  function $reset(): void {
    user.value = null;
    tenants.value = [];
    isLoading.value = false;
    isInitialized.value = false;
    twoFactorToken.value = null;
    isImpersonating.value = false;
    impersonationExpiresAt.value = null;
    originalToken.value = null;
  }

  return {
    user,
    tenants,
    isLoading,
    isInitialized,
    twoFactorToken,
    isImpersonating,
    impersonationExpiresAt,
    isAuthenticated,
    isSuperAdmin,
    hasTwoFactor,
    needsTwoFactor,
    login,
    completeTwoFactor,
    completeTwoFactorRecovery,
    logout,
    fetchUser,
    initialize,
    setUser,
    setTenants,
    startImpersonation,
    stopImpersonation,
    $reset,
  };
});
