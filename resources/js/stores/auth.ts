import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/api';
import type { User, Tenant, LoginCredentials } from '@/api/types';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const tenants = ref<Tenant[]>([]);
    const isLoading = ref(false);
    const isInitialized = ref(false);

    const isAuthenticated = computed(() => !!user.value);
    const isSuperAdmin = computed(() => user.value?.is_super_admin ?? false);
    const hasTwoFactor = computed(() => !!user.value?.two_factor_secret);

    async function login(credentials: LoginCredentials): Promise<void> {
        isLoading.value = true;
        try {
            const { data } = await authApi.login(credentials);
            localStorage.setItem('access_token', data.access_token);
            localStorage.setItem('refresh_token', data.refresh_token);
            user.value = data.user;
            tenants.value = data.tenants;
        } finally {
            isLoading.value = false;
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
    }

    return {
        user,
        tenants,
        isLoading,
        isInitialized,
        isAuthenticated,
        isSuperAdmin,
        hasTwoFactor,
        login,
        logout,
        fetchUser,
        initialize,
        setUser,
        setTenants,
        $reset,
    };
});
