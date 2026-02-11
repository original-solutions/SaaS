import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';

const routes: RouteRecordRaw[] = [
    // ─── Guest routes ──────────────────────────────────────────
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/auth/ForgotPasswordPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/reset-password/:token',
        name: 'reset-password',
        component: () => import('@/pages/auth/ResetPasswordPage.vue'),
        meta: { guest: true },
    },

    // ─── App routes (authenticated + tenant) ──────────────────────────────────────────
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('@/pages/app/DashboardPage.vue'),
            },
            {
                path: 'customers',
                name: 'customers',
                component: () => import('@/pages/app/customers/CustomerListPage.vue'),
            },
            {
                path: 'customers/create',
                name: 'customer-create',
                component: () => import('@/pages/app/customers/CustomerCreatePage.vue'),
            },
            {
                path: 'customers/:id',
                name: 'customer-show',
                component: () => import('@/pages/app/customers/CustomerShowPage.vue'),
            },
            {
                path: 'customers/:id/edit',
                name: 'customer-edit',
                component: () => import('@/pages/app/customers/CustomerEditPage.vue'),
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('@/pages/app/SettingsPage.vue'),
            },
            {
                path: 'settings/team',
                name: 'team-settings',
                component: () => import('@/pages/app/TeamSettingsPage.vue'),
            },
        ],
    },

    // ─── Admin routes ──────────────────────────────────────────
    {
        path: '/admin',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, requiresSuperAdmin: true },
        children: [
            {
                path: '',
                name: 'admin-dashboard',
                component: () => import('@/pages/admin/AdminDashboardPage.vue'),
            },
            {
                path: 'tenants',
                name: 'admin-tenants',
                component: () => import('@/pages/admin/AdminTenantsPage.vue'),
            },
            {
                path: 'users',
                name: 'admin-users',
                component: () => import('@/pages/admin/AdminUsersPage.vue'),
            },
        ],
    },

    // ─── Catch-all ──────────────────────────────────────────
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/pages/NotFoundPage.vue'),
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // Ensure the auth store is initialized (loads user from token)
    if (!auth.isInitialized) {
        await auth.initialize();
    }

    // Guest-only routes: redirect authenticated users to dashboard
    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    // Auth-required routes: redirect unauthenticated users to login
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    // Super admin routes
    if (to.meta.requiresSuperAdmin && !auth.isSuperAdmin) {
        return { name: 'dashboard' };
    }

    // Ensure tenant is selected for app routes
    if (to.meta.requiresAuth && !to.meta.requiresSuperAdmin && auth.isAuthenticated) {
        const tenant = useTenantStore();
        if (!tenant.current && auth.tenants.length > 0) {
            tenant.initialize(auth.tenants);
        }
    }
});
