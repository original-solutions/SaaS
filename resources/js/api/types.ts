export interface User {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
    two_factor_secret: string | null;
    email_verified_at: string | null;
    last_login_at: string | null;
    locked_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Tenant {
    id: number;
    name: string;
    slug: string;
    status: 'active' | 'inactive' | 'suspended';
    plan: string | null;
    trial_ends_at: string | null;
    disabled_at: string | null;
    created_at: string;
    updated_at: string;
    pivot?: { role: TenantRole };
}

export type TenantRole = 'owner' | 'admin' | 'member' | 'readonly';

export interface Customer {
    id: number;
    tenant_id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company: string | null;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface Tag {
    id: number;
    tenant_id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Note {
    id: number;
    tenant_id: number;
    author_user_id: number;
    noteable_type: string;
    noteable_id: number;
    body: string;
    created_at: string;
    updated_at: string;
}

export interface SavedView {
    id: number;
    tenant_id: number;
    user_id: number;
    resource_type: string;
    name: string;
    config: Record<string, unknown>;
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

export interface Plan {
    id: number;
    name: string;
    stripe_price_id: string;
    features: Record<string, unknown>;
    is_active: boolean;
    sort_order: number;
}

export interface DeviceSession {
    id: number;
    user_id: number;
    device_name: string;
    ip_address: string;
    last_active_at: string;
    revoked_at: string | null;
    created_at: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface ApiResponse<T> {
    data: T;
}

export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface LoginResponse {
    access_token: string;
    refresh_token: string;
    user: User;
    tenants: Tenant[];
}

export interface RegisterData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}
