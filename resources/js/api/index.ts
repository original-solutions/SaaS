import api from './client';
import type {
    LoginCredentials,
    LoginResponse,
    User,
    Tenant,
    Plan,
    ApiResponse,
    PaginatedResponse,
    Customer,
    Tag,
    SavedView,
    DeviceSession,
} from './types';

// ─── Auth ──────────────────────────────────────────────

export const authApi = {
    login(credentials: LoginCredentials) {
        return api.post<LoginResponse>('/auth/login', credentials);
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
        return api.post('/2fa/setup');
    },

    confirm(code: string) {
        return api.post('/2fa/confirm', { code });
    },

    disable(code: string) {
        return api.delete('/2fa', { data: { code } });
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
