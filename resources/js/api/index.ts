import api from './client';
import type {
    LoginCredentials,
    LoginResult,
    User,
    Tenant,
    Plan,
    ApiResponse,
    PaginatedResponse,
    Customer,
    Tag,
    SavedView,
    DeviceSession,
    PersonalAccessToken,
    Activity,
    Notification,
    File,
    Import,
    Invitation,
    TwoFactorSetupResponse,
    AdminTenantDetail,
    AdminUserDetail,
    HealthCheck,
    Subscription,
} from './types';

// ─── Auth ──────────────────────────────────────────────

export const authApi = {
    login(credentials: LoginCredentials) {
        return api.post<LoginResult>('/auth/login', credentials);
    },

    twoFactorChallenge(data: { two_factor_token: string; code: string }) {
        return api.post<LoginResult>('/auth/2fa-challenge', data);
    },

    twoFactorRecovery(data: { two_factor_token: string; recovery_code: string }) {
        return api.post<LoginResult>('/auth/2fa-recovery', data);
    },

    requestMagicLink(email: string) {
        return api.post('/auth/magic-link', { email });
    },

    logout() {
        return api.post('/auth/logout');
    },

    refresh(refreshToken: string) {
        return api.post<{ access_token: string; refresh_token: string }>('/auth/refresh', {
            refresh_token: refreshToken,
        });
    },

    me() {
        return api.get<ApiResponse<User>>('/me');
    },

    forgotPassword(email: string) {
        return api.post('/auth/forgot-password', { email });
    },

    resetPassword(data: {
        token: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) {
        return api.post('/auth/reset-password', data);
    },

    verifyEmail(url: string) {
        return api.get(url);
    },

    resendVerification() {
        return api.post('/email/verification-notification');
    },
};

// ─── Account ──────────────────────────────────────────

export const accountApi = {
    changePassword(data: {
        current_password: string;
        password: string;
        password_confirmation: string;
    }) {
        return api.put('/account/password', data);
    },

    changeEmail(data: { email: string; password: string }) {
        return api.put('/account/email', data);
    },

    exportData() {
        return api.post('/account/export');
    },

    deleteAccount(password: string) {
        return api.delete('/account', { data: { password } });
    },
};

// ─── Two-Factor ──────────────────────────────────────────

export const twoFactorApi = {
    setup() {
        return api.post<ApiResponse<TwoFactorSetupResponse>>('/2fa/setup');
    },

    confirm(code: string) {
        return api.post<ApiResponse<{ recovery_codes: string[] }>>('/2fa/confirm', { code });
    },

    disable(code: string) {
        return api.delete('/2fa', { data: { code } });
    },

    recoveryCodes() {
        return api.get<ApiResponse<{ recovery_codes: string[] }>>('/2fa/recovery-codes');
    },
};

// ─── Tenants ──────────────────────────────────────────

export const tenantApi = {
    list() {
        return api.get<ApiResponse<Tenant[]>>('/tenants');
    },

    create(data: { name: string }) {
        return api.post<ApiResponse<Tenant>>('/tenants', data);
    },

    show(id: number) {
        return api.get<ApiResponse<Tenant>>(`/tenants/${id}`);
    },

    update(id: number, data: Partial<Tenant>) {
        return api.put<ApiResponse<Tenant>>(`/tenants/${id}`, data);
    },

    members(id: number) {
        return api.get(`/tenants/${id}/members`);
    },
};

// ─── Invitations ──────────────────────────────────────────

export const invitationApi = {
    send(data: { email: string; role: string }) {
        return api.post('/invitations', data);
    },

    resend(id: number) {
        return api.post(`/invitations/${id}/resend`);
    },

    updateRole(id: number, role: string) {
        return api.put(`/invitations/${id}/role`, { role });
    },

    cancel(id: number) {
        return api.delete(`/invitations/${id}`);
    },
};

// ─── Device Sessions ──────────────────────────────────────────

export const sessionApi = {
    list() {
        return api.get<ApiResponse<DeviceSession[]>>('/sessions');
    },

    revoke(id: number) {
        return api.delete(`/sessions/${id}`);
    },

    revokeAll() {
        return api.post('/sessions/revoke-all');
    },
};

// ─── Billing ──────────────────────────────────────────

export const billingApi = {
    current() {
        return api.get('/billing');
    },

    plans() {
        return api.get<ApiResponse<Plan[]>>('/billing/plans');
    },
};

// ─── Customers ──────────────────────────────────────────

export const customerApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<Customer>>('/customers', { params });
    },

    create(data: Partial<Customer>) {
        return api.post<ApiResponse<Customer>>('/customers', data);
    },

    show(id: number) {
        return api.get<ApiResponse<Customer>>(`/customers/${id}`);
    },

    update(id: number, data: Partial<Customer>) {
        return api.put<ApiResponse<Customer>>(`/customers/${id}`, data);
    },

    delete(id: number) {
        return api.delete(`/customers/${id}`);
    },
};

// ─── Tags ──────────────────────────────────────────

export const tagApi = {
    list() {
        return api.get<PaginatedResponse<Tag>>('/tags');
    },

    create(data: { name: string }) {
        return api.post<ApiResponse<Tag>>('/tags', data);
    },

    update(id: number, data: { name: string }) {
        return api.put<ApiResponse<Tag>>(`/tags/${id}`, data);
    },

    delete(id: number) {
        return api.delete(`/tags/${id}`);
    },

    attach(id: number, data: { taggable_type: string; taggable_id: number }) {
        return api.post(`/tags/${id}/attach`, data);
    },

    detach(id: number, data: { taggable_type: string; taggable_id: number }) {
        return api.post(`/tags/${id}/detach`, data);
    },
};

// ─── Saved Views ──────────────────────────────────────────

export const savedViewApi = {
    list(resourceType?: string) {
        return api.get<ApiResponse<SavedView[]>>('/saved-views', {
            params: resourceType ? { resource_type: resourceType } : {},
        });
    },

    create(data: Partial<SavedView>) {
        return api.post<ApiResponse<SavedView>>('/saved-views', data);
    },

    update(id: number, data: Partial<SavedView>) {
        return api.put<ApiResponse<SavedView>>(`/saved-views/${id}`, data);
    },

    delete(id: number) {
        return api.delete(`/saved-views/${id}`);
    },

    setDefault(id: number) {
        return api.post(`/saved-views/${id}/default`);
    },
};

// ─── Notes ──────────────────────────────────────────

export const noteApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<import('./types').Note>>('/notes', { params });
    },

    create(data: { noteable_type: string; noteable_id: number; body: string }) {
        return api.post<ApiResponse<import('./types').Note>>('/notes', data);
    },

    update(id: number, data: { body: string }) {
        return api.put<ApiResponse<import('./types').Note>>(`/notes/${id}`, data);
    },

    delete(id: number) {
        return api.delete(`/notes/${id}`);
    },
};

// ─── Files ──────────────────────────────────────────

export const fileApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<File>>('/files', { params });
    },

    upload(data: FormData) {
        return api.post<ApiResponse<File>>('/files', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    },

    download(id: number) {
        return api.get(`/files/${id}/download`, { responseType: 'blob' });
    },

    delete(id: number) {
        return api.delete(`/files/${id}`);
    },
};

// ─── Imports ──────────────────────────────────────────

export const importApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<Import>>('/imports', { params });
    },

    create(data: FormData) {
        return api.post<ApiResponse<Import>>('/imports', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    },

    show(id: number) {
        return api.get<ApiResponse<Import>>(`/imports/${id}`);
    },

    retry(id: number) {
        return api.post(`/imports/${id}/retry`);
    },
};

// ─── Notifications ──────────────────────────────────────────

export const notificationApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<Notification>>('/notifications', { params });
    },

    markRead(id: string) {
        return api.post(`/notifications/${id}/read`);
    },

    markAllRead() {
        return api.post('/notifications/read-all');
    },

    unreadCount() {
        return api.get<ApiResponse<{ count: number }>>('/notifications/unread-count');
    },
};

// ─── Personal Access Tokens ──────────────────────────────────────────

export const tokenApi = {
    list() {
        return api.get<ApiResponse<PersonalAccessToken[]>>('/tokens');
    },

    create(data: { name: string; abilities?: string[] }) {
        return api.post<ApiResponse<{ token: string; accessToken: PersonalAccessToken }>>('/tokens', data);
    },

    revoke(id: number) {
        return api.delete(`/tokens/${id}`);
    },
};

// ─── Activity Log ──────────────────────────────────────────

export const activityApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<Activity>>('/activity', { params });
    },
};

// ─── Invitations (public) ──────────────────────────────────────────

export const invitePublicApi = {
    show(token: string) {
        return api.get<ApiResponse<Invitation>>(`/invitations/accept/${token}`);
    },

    accept(token: string) {
        return api.post<ApiResponse<{ tenant: Tenant }>>(`/invitations/accept/${token}`);
    },
};

// ─── Admin ──────────────────────────────────────────

const adminApi = api.create?.({ baseURL: '/admin/api/v1' }) ?? api;

export const adminTenantsApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<AdminTenantDetail>>('/admin/tenants', { params });
    },

    show(id: number) {
        return api.get<ApiResponse<AdminTenantDetail>>(`/admin/tenants/${id}`);
    },

    disable(id: number) {
        return api.post(`/admin/tenants/${id}/disable`);
    },

    enable(id: number) {
        return api.post(`/admin/tenants/${id}/enable`);
    },

    members(id: number) {
        return api.get<ApiResponse<User[]>>(`/admin/tenants/${id}/members`);
    },
};

export const adminUsersApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<AdminUserDetail>>('/admin/users', { params });
    },

    show(id: number) {
        return api.get<ApiResponse<AdminUserDetail>>(`/admin/users/${id}`);
    },

    lock(id: number) {
        return api.post(`/admin/users/${id}/lock`);
    },

    unlock(id: number) {
        return api.post(`/admin/users/${id}/unlock`);
    },

    impersonate(id: number) {
        return api.post<ApiResponse<{ access_token: string; expires_at: string }>>(`/admin/users/${id}/impersonate`);
    },

    stopImpersonation() {
        return api.post('/admin/impersonation/stop');
    },
};

export const adminJobsApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<Record<string, unknown>>>('/admin/jobs', { params });
    },

    failedJobs(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<Record<string, unknown>>>('/admin/jobs/failed', { params });
    },

    retry(id: number) {
        return api.post(`/admin/jobs/failed/${id}/retry`);
    },

    retryAll() {
        return api.post('/admin/jobs/failed/retry-all');
    },
};

export const adminAuditApi = {
    list(params?: Record<string, unknown>) {
        return api.get<PaginatedResponse<Activity>>('/admin/audit', { params });
    },
};

export const adminHealthApi = {
    check() {
        return api.get<ApiResponse<HealthCheck[]>>('/admin/health');
    },
};

export const adminSearchApi = {
    search(query: string) {
        return api.get<ApiResponse<{
            tenants: Tenant[];
            users: User[];
        }>>('/admin/search', { params: { q: query } });
    },
};
