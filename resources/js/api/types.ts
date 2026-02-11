export interface User {
  id: number;
  name: string;
  email: string;
  is_super_admin: boolean;
  two_factor_secret: string | null;
  two_factor_confirmed_at: string | null;
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

export interface PersonalAccessToken {
  id: number;
  name: string;
  abilities: string[];
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface Activity {
  id: number;
  log_name: string;
  description: string;
  subject_type: string | null;
  subject_id: number | null;
  causer_type: string | null;
  causer_id: number | null;
  properties: Record<string, unknown>;
  created_at: string;
}

export interface Notification {
  id: string;
  type: string;
  data: Record<string, unknown>;
  read_at: string | null;
  created_at: string;
}

export interface File {
  id: number;
  tenant_id: number;
  uploaded_by: number;
  fileable_type: string;
  fileable_id: number;
  name: string;
  path: string;
  mime_type: string;
  size: number;
  created_at: string;
}

export interface Import {
  id: number;
  tenant_id: number;
  user_id: number;
  entity_type: string;
  file_name: string;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  total_rows: number | null;
  processed_rows: number | null;
  failed_rows: number | null;
  errors: Record<string, unknown> | null;
  created_at: string;
  completed_at: string | null;
}

export interface Invitation {
  id: number;
  tenant_id: number;
  email: string;
  role: TenantRole;
  token: string;
  accepted_at: string | null;
  expires_at: string;
  created_at: string;
}

export interface Subscription {
  id: number;
  tenant_id: number;
  plan_id: number;
  status: 'active' | 'past_due' | 'cancelled' | 'trialing';
  current_period_start: string;
  current_period_end: string;
  grace_period_end: string | null;
  plan?: Plan;
}

export interface TwoFactorSetupResponse {
  qr_code: string;
  secret: string;
  recovery_codes: string[];
}

export interface LoginResponse2FA {
  two_factor: true;
  two_factor_token: string;
}

export interface AdminTenantDetail extends Tenant {
  users_count: number;
  customers_count: number;
  subscription?: Subscription;
}

export interface AdminUserMembership {
  tenant_id: number;
  tenant_name: string;
  role: string;
}

export interface AdminUserDetail extends User {
  tenants_count: number;
  sessions_count: number;
  login_events: LoginEvent[];
  memberships: AdminUserMembership[];
}

export interface LoginEvent {
  id: number;
  user_id: number;
  event_type: string;
  ip_address: string;
  user_agent: string;
  created_at: string;
}

export interface HealthCheck {
  name: string;
  status: 'ok' | 'warning' | 'critical';
  message: string;
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
  two_factor?: false;
}

export type LoginResult = LoginResponse | LoginResponse2FA;

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}
