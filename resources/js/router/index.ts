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
  {
    path: '/magic-link',
    name: 'magic-link',
    component: () => import('@/pages/auth/MagicLinkPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/invitations/accept/:token',
    name: 'accept-invite',
    component: () => import('@/pages/auth/AcceptInvitePage.vue'),
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('@/pages/auth/VerifyEmailPage.vue'),
    meta: { requiresAuth: true },
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
        path: 'notes',
        name: 'notes',
        component: () => import('@/pages/app/NotesPage.vue'),
      },
      {
        path: 'files',
        name: 'files',
        component: () => import('@/pages/app/FilesPage.vue'),
      },
      {
        path: 'imports',
        name: 'imports',
        component: () => import('@/pages/app/ImportsPage.vue'),
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: () => import('@/pages/app/NotificationsPage.vue'),
      },
      {
        path: 'billing',
        name: 'billing',
        component: () => import('@/pages/app/billing/BillingPage.vue'),
      },
      {
        path: 'billing/plans',
        name: 'billing-plans',
        component: () => import('@/pages/app/billing/PlansPage.vue'),
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/pages/app/SettingsPage.vue'),
      },
      {
        path: 'settings/sessions',
        name: 'settings-sessions',
        component: () => import('@/pages/app/settings/SessionsPage.vue'),
      },
      {
        path: 'settings/tokens',
        name: 'settings-tokens',
        component: () => import('@/pages/app/settings/TokensPage.vue'),
      },
      {
        path: 'settings/two-factor',
        name: 'settings-two-factor',
        component: () => import('@/pages/app/settings/TwoFactorPage.vue'),
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
        path: 'tenants/:id',
        name: 'admin-tenant-detail',
        component: () => import('@/pages/admin/AdminTenantDetailPage.vue'),
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('@/pages/admin/AdminUsersPage.vue'),
      },
      {
        path: 'users/:id',
        name: 'admin-user-detail',
        component: () => import('@/pages/admin/AdminUserDetailPage.vue'),
      },
      {
        path: 'jobs',
        name: 'admin-jobs',
        component: () => import('@/pages/admin/AdminJobsPage.vue'),
      },
      {
        path: 'audit',
        name: 'admin-audit',
        component: () => import('@/pages/admin/AdminAuditPage.vue'),
      },
      {
        path: 'health',
        name: 'admin-health',
        component: () => import('@/pages/admin/AdminHealthPage.vue'),
      },
      {
        path: 'search',
        name: 'admin-search',
        component: () => import('@/pages/admin/AdminSearchPage.vue'),
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
