# Laravel SaaS CRUD + Admin Base Template

**Stack:** Laravel 12, MySQL, Vue 3 (SPA), Tailwind CSS, Sanctum, Reverb, Pest, Prettier, Pint

**Purpose:**
A production-grade, opinionated SaaS foundation that can be reused across multiple products. This template is not a demo or starter kit — it is a scalable base with real-world SaaS concerns solved upfront.

---

## 1. Core Architecture

### 1.0 API Versioning (Required)

- All endpoints are versioned under `/api/v1/*` (recommended)
- Versioning rules:
    - Non-breaking changes allowed within v1
    - Breaking changes create `/api/v2/*`

### 1.1 Application Model

- Single codebase
- SPA frontend (Vue Router)
- Laravel API backend
- Token-based Sanctum authentication (Bearer tokens) with a documented refresh + revoke model
- Single database with `tenant_id` scoping

### 1.2 Tenancy Model

- Tenant = Organisation / Workspace
- Users can belong to multiple tenants
- Pivot table: `tenant_user`
    - role
    - joined_at

### 1.3 Roles

**Tenant roles:**

- Owner
- Admin
- Member
- Read-only

**Platform roles:**

- Super Admin

---

## 2. Authentication & Security

### 2.1 Authentication

- Email + password login
- Email verification
- Password reset
- Optional magic-link login
- Optional 2FA (TOTP + recovery codes)
- Session management UI
- Login history tracking

### 2.2 Sanctum

- Bearer token (Authorization: Bearer <access_token>) authentication
- `/api/v1/me` endpoint
- Personal access tokens (optional, for integrations)
- Token management UI

#### 2.2.1 SPA Token Storage & Security Model (Required)

**Goal:** reduce XSS blast radius while keeping a reliable “reload” story.

**Rules:**

- **Never store access tokens in localStorage.**
- Store **access tokens in memory** (Vue store) only.
- Access tokens are **never persisted** (no localStorage/sessionStorage/IndexedDB).
- Access tokens are **cleared on reload**; reload triggers the refresh flow (if refresh token present) or re-auth if not.
- Persist **refresh tokens** using a **token-only** approach (required):
    - Refresh token stored client-side with **at-rest obfuscation** (best-effort) + strict CSP + rotation + reuse detection
    - If this template is mainly for internal/owned products, it is acceptable to require more frequent re-authentication to reduce refresh token lifetime client-side

**Refresh flow (must be implemented and tested):**

1. Login returns `{ access_token, refresh_token, expires_in }`
2. SPA stores `access_token` in memory and persists `refresh_token` (token-only approach)
3. On 401/expiry, SPA calls `POST /api/v1/auth/refresh`
4. Server rotates refresh token (one-time use) and returns new `{ access_token, refresh_token }`
5. Old refresh token is invalidated immediately
6. Logout revokes refresh token and related access tokens

**Device/session model (required):**

- Treat each login as a **device session**.
- Refresh tokens are issued **per device session** (not shared).

**Tables (required):**

- `device_sessions`
    - `id`
    - `user_id`
    - `device_label` (string, nullable)
    - `ip_address` (string, nullable)
    - `user_agent` (text, nullable)
    - `last_used_at` (timestamp, nullable)
    - `revoked_at` (timestamp, nullable)
    - `created_at`, `updated_at`
- `refresh_tokens`
    - `id`
    - `user_id`
    - `device_session_id`
    - `token_hash` (string, unique)
    - `expires_at` (timestamp)
    - `revoked_at` (timestamp, nullable)
    - `replaced_by_id` (nullable, self FK)
    - `last_used_at` (timestamp, nullable)
    - `created_at`

**Reuse detection behaviour (required):**

- If a refresh token is presented that is already **revoked** or already **used/replaced**, treat as reuse.
- Response:
    - Immediately **revoke the entire device session chain** (revoke all refresh tokens in that session and invalidate related access tokens)
    - Create a high-severity **security event** (audit + admin notification)
    - Optionally (config): revoke **all** user device sessions if you prefer a stricter stance

**Revocation rules:**

- Revoke all tokens on password change/reset
- Support per-device sessions (token label + last_used_at)

**Hardening requirements:**

- **Strict CSP required**: no `unsafe-inline`, nonce-based scripts
- Short access token TTL: **10–15 minutes**
- Refresh token rotation: **one-time use** tokens
- **Reuse detection**: if a refresh token is reused, revoke session chain and alert (admin + audit)
- Refresh endpoint heavily rate-limited + per-device/session tracking
- Audit refresh token reuse as a potential theft signal

### 2.3 Security Defaults

- Form Request validation everywhere
- Mass assignment protection
- Rate limiting by route group
- Signed URLs for invites and magic links
- CORS preconfigured (CSRF not required — no auth cookies used).
- Security headers baseline (CSP, HSTS, X-Content-Type-Options, Referrer-Policy)
- Password change/reset revokes tokens (required)

#### 2.3.1 XSS Mitigation Beyond CSP (Required)

- Vue templates rely on automatic escaping by default; **avoid** `v-html`.
- If rich text is needed:
    - sanitise on write using an allowlist (e.g., basic formatting only)
    - re-sanitise on render as defence-in-depth
    - store both raw + sanitised versions if required for auditing
- Input validation for any user-supplied HTML/markdown.
- Dependency hygiene:
    - lockfiles committed
    - routine dependency audit (CI job)
    - prohibit abandoned packages for auth/crypto/sanitisation
- Strict content handling:
    - no inline scripts
    - no dynamic script injection
    - avoid `eval`/Function constructors

---

## 3. Multi-Tenancy

### 3.1 Tenant Context

- Tenant resolved via explicit tenant context (stored in SPA state + sent via header `X-Tenant-ID`)
- Tenant switcher in app header
- Middleware enforces tenant scoping

#### 3.1.0 No Tenant Selected Behaviour (Required)

If `X-Tenant-ID` is missing, only the following endpoints may succeed:

- Auth endpoints (login, refresh, logout)
- `GET /api/v1/me`
- List user’s tenants / workspaces
- Accept invite flow

All other tenant-scoped endpoints must return **400** with a clear error: `TENANT_REQUIRED`.

#### 3.1.1 Tenant Context Security (Required)

**Never trust `X-Tenant-ID` blindly.** Server must enforce:

- Authenticated user **must** be a member of the tenant
- Tenant must be `active` (unless Super Admin bypass in Admin area)
- All tenant-scoped queries must apply `tenant_id` constraint

**ResolveTenant middleware:**

- Validate header present
- Load tenant
- Confirm membership via `tenant_user`
- Set tenant into request context

**Tenant identification strategy (choose and document):**

- Header-only (`X-Tenant-ID`) **or**
- Subdomain (`{tenant}.app.com`) **or**
- Both (subdomain primary, header for internal tools)

### 3.2 Invitations

- Invite users by email
- Signed invite links
- Accept/decline flow
- Role assignment on accept

#### 3.2.1 Invite Edge Cases & Rules (Required)

- Invited email already exists:
    - If the user exists, accepting the invite attaches membership to the tenant.
- Invite sent to an email different from the currently logged-in user:
    - Do **not** allow acceptance while logged in as a different email.
    - Provide a “switch account” or “log out and continue” flow.
- Expiry + resend:
    - Invites expire (configurable, e.g. 7 days)
    - Resend generates a new token and invalidates the old one
    - Rate limit resends per inviter + per tenant
- Duplicate invites:
    - If a pending invite exists for (tenant, email), resend instead of creating another record
- Email verification:
    - Require verified email before accepting invite (or verify during accept flow)
- Role changes:
    - Allow inviter (Owner/Admin) to change role on a pending invite

### 3.3 Tenant Lifecycle

- Create tenant
- Disable / enable tenant
- Soft delete + hard delete with delay
- Export tenant data

### 3.4 User Account Lifecycle (Required)

- User export personal data (profile + memberships + audit events where actor)
- User delete account:
    - If user is the **sole Owner** of a tenant, block deletion until ownership transferred
    - On delete, remove memberships and revoke all tokens/device sessions
    - Decide retention of authored content (notes/activity):
        - either anonymise actor fields, or retain user_id for audit (policy decision)
- User lock/unlock (admin)
- User email change flow:
    - require re-verification
    - revoke tokens optionally (recommended)

---

## 4. Authorisation

- Policies for all models
- Gates for high-level permissions
- UI hides or disables unauthorised actions
- Policy tests included for all CRUD resources

### 4.1 RBAC Extension Path (Required Note)

- Start with roles (Owner/Admin/Member/Read-only)
- Extension path to permissions/abilities later:
    - `permissions` + `role_permissions`, or
    - config-driven abilities with overrides
- Keep policy checks structured so introducing permissions later is low-impact

---

## 5. CRUD Resource System

### 5.1 Resource Generator

One command generates:

- Migration
- Model
- Factory
- Policy
- Controller
- Form Requests
- Routes
- Vue pages (Index, Show, Create, Edit)
- Vue components (Form, Table)
- Pest feature tests
- Demo seed data

### 5.2 List Views

- Pagination
- Sorting
- Column visibility
- Search (debounced)
- Filters
- Saved views
- Bulk actions
- Row action menus
- Empty states

### 5.3 Forms

- Reusable field components
- Backend validation error display
- Dirty state detection
- Unsaved changes guard
- Optional autosave
- File uploads

---

## 6. Frontend Architecture

### 6.1 App Shell

- Sidebar navigation
- Collapsible sections
- Command palette (Ctrl+K)
- Global toast notifications
- Route loading indicator
- Light/Dark mode

### 6.2 Data Layer

- Central API client
- Unified error handling
- Request ID propagation
- List parameter helpers
- Optimistic updates

### 6.3 Component Library

**UI Stack Recommendations (Standardise for Consistency):**

- **UI primitives:** shadcn-vue + Tailwind CSS
- **Tables:** TanStack Table (use when advanced filtering/sorting/grouping is required)
- **Forms:** vee-validate + zod (schema-driven validation shared with backend rules where possible)
- **Icons:** lucide

**Implementation principles:**

- Wrap shadcn components in local abstraction components (do not couple pages directly to vendor components)
- Centralise form validation schemas per domain (co-locate with API types)
- Use TanStack Table only when simple table abstraction is insufficient

Base components to ship in template:

- Button
- Dropdown
- Modal
- Drawer
- Tabs
- Breadcrumbs
- Confirm dialog service
- Base data table component (lightweight wrapper over TanStack when needed)

---

## 7. Realtime (Reverb)

### 7.1 Patterns

- Online/offline indicator
- Background job completion notifications
- Soft refresh prompts for updated records
- Presence indicators on edit pages

---

## 8. Notifications, Activity Log & Deliverability

### 8.0 Deliverability Tooling (Email/SMS)

- Per-tenant sending limits (rate + daily caps)
- Suppression list (global + per-tenant)
- Unsubscribe handling (email)
- Complaints/bounces tracking
- Message logs accessible to Super Admin (see Ops)

### 8.1 Notifications

- Database notifications
- Notification dropdown
- Notifications page
- Read/unread state
- Per-user preferences

### 8.2 Activity Log

- Actor
- Impersonator (nullable)
- Action
- Target model
- Before/after snapshot
- Timestamp

#### 8.2.1 Data Retention & Privacy (Required)

- Define retention policy per tenant (default configurable)
- Activity snapshots may contain PII:
    - support redaction rules for specific fields
    - support tenant export
    - support tenant deletion workflow (hard delete after delay)
- Document what is retained after tenant deletion (e.g., billing events for compliance)

---

## 9. Background Jobs, Ops, Backups & DR

#### 9.0 Backups & Disaster Recovery (Required)

- Environment separation: dev / staging / production
- Automated DB backups (frequency + retention)
- File storage backups (if not using managed versioning)
- Restore runbook:
    - restore DB to point-in-time
    - restore files
    - smoke test checklist
- DR objectives documented: RPO / RTO

### 9.0A Zero-Downtime Deploy & Release Checklist (Required)

**Deploy principles:**

- Backwards-compatible changes first; destructive changes last
- Prefer expand/contract migrations

**Checklist:**

- Run migrations safely (no long locks; add indexes concurrently where applicable)
- Warm config cache / route cache (if used)
- Restart queue workers after deploy
- Verify scheduler is running
- Health endpoint check (HTTP 200 + DB + queue + broadcast)
- Smoke tests:
    - login
    - tenant switch
    - CRUD create/update
    - refresh token cycle
    - admin impersonation
- Rollback plan:
    - application rollback
    - migration rollback strategy (avoid irreversible migrations without plan)

### 9.1 Queue

- Configured queue driver
- Failed jobs table
- Retry UI

### 9.2 Jobs

- Job progress tracking
- Example: CSV import job
- UI progress indicator

### 9.3 Scheduler

- Central scheduler registration
- Cleanup tasks

### 9.4 Observability & Instrumentation

- Correlation IDs
- Central exception handling
- Slow query logging toggle
- Structured JSON logs (recommended)
- APM hooks/toggles (recommended): Sentry (errors), PostHog (product analytics), OpenTelemetry (tracing)

---

## 10. Admin Section

### 10.1 Access Control

- `/admin/*` route group
- Super Admin only
- Step-up authentication for sensitive actions

### 10.2 Admin UI

- Separate Admin layout
- Global admin search
- Recent admin actions panel

### 10.3 Tenant Management

- Tenant list with filters
- Tenant detail view
- Members management
- Subscription status (stub)
- Usage stats
- Actions:
    - Disable / enable
    - Force logout
    - Export data
    - Delete tenant

### 10.4 User Management

- User list with filters
- User detail view
- Membership overview
- Sessions + login history
- Actions:
    - Lock / unlock
    - Reset password
    - Revoke sessions

### 10.5 Support Tools

- Admin-only notes
- Support cases
- Feature flag overrides

### 10.6 Ops Dashboard

- Jobs dashboard
- Failed jobs retry
- Mail/SMS logs
- Webhook logs
- System health checks

---

## 11. Impersonation

### 11.1 Rules

- Super Admin only
- Cannot impersonate other super admins
- Requires step-up auth
- Time-limited session

### 11.2 UX

- Impersonate button on user page
- Persistent banner during impersonation
- Stop impersonation button

### 11.3 Audit

- Log impersonation start/stop
- Store impersonator ID
- Store reason (optional)

---

## 12. Billing (Stub)

> Billing is not “stubbed” unless webhook + idempotency + state transitions are defined.

### 12.1 Billing Models

- Plan model
- Subscription status on tenant
- Middleware: subscribed / trialing / past_due
- Billing UI placeholders

### 12.2 Stripe Integration Requirements (Recommended)

- Stripe customer per tenant
- Stripe subscription per tenant
- Webhook endpoint: `POST /api/v1/webhooks/stripe`

### 12.3 Webhook Processing (Required)

- Verify Stripe signature
- **Idempotency**: store and dedupe events by **(provider, event_id)**
- Retries: safe to reprocess same event
- Background processing via queue

**Storage:**

- Use the **generic `webhook_events`** table for Stripe as well (recommended) so all providers share one pattern.
- Keep `subscriptions` (and optional `invoices`) as billing domain tables.

### 12.4 Subscription State Behaviour

Define and implement:

- `trialing` → full access
- `active` → full access
- `past_due` → grace period (configurable), then read-only mode
- `canceled`/`unpaid` → locked/read-only with upgrade CTA

**Read-only mode rules:**

- Block writes for tenant-scoped resources
- Allow read access + exports
- Allow billing page access

---

## 13. Testing Strategy

### 13.0 Rate Limiting Defaults (Required)

Define concrete defaults and enforce **per-user and per-tenant**, not just IP.

**Auth routes**

- `POST /auth/login`: e.g. 10/min per IP + 5/min per email
- `POST /auth/refresh`: e.g. 30/min per device_session + 60/min per user
- `POST /auth/logout`: e.g. 30/min per user

**Invites**

- `POST /tenants/{id}/invites`: e.g. 20/hour per tenant + 10/hour per inviter
- `POST /invites/{id}/resend`: e.g. 5/hour per email

**Admin**

- Impersonate start/stop: e.g. 30/hour per super admin

**Outbound (email/SMS)**

- Per-tenant daily caps + burst limits (configurable)

Implement with Laravel rate limiters keyed by:

- user_id
- tenant_id
- device_session_id
- fallback IP

### 13.1 Pest

- CRUD tests
- Auth tests
- Policy tests
- Tenancy isolation tests
- Impersonation tests

### 13.2 Helpers

- actingAsTenantOwner()
- actingAsMember()
- actingAsSuperAdmin()

---

## 14. Developer Experience

### 14.1 Code Quality

- Prettier
- ESLint
- Pint
- Pre-commit hooks

### 14.2 CI

- Pest
- Static analysis (optional)
- Frontend build

### 14.3 Setup

- `.env.example`
- One-command install
- Seed demo tenant + admin
- Feature flags system

---

## 14A. Recommended Folder Structure (Laravel Patterns, No Modules)

**Goal:** predictable, scalable structure that keeps domain logic out of controllers, makes tenancy + authorisation hard to get wrong, and supports fast scaffolding.

### 14A.1 Backend (Laravel)

Use a domain-oriented structure under `app/` (no `modules/`, no nwidart).

```
app/
  Console/
  Http/
    Controllers/
      Api/
        V1/
      Admin/
    Middleware/
    Requests/
      Api/
        V1/
  Models/
  Policies/
  Providers/
  Actions/
  Services/
  DTO/
  Enums/
  Support/
    Auth/
    Tenancy/
    Impersonation/
    Query/
  Events/
  Listeners/
  Notifications/
  Jobs/
  Rules/

routes/
  api_v1.php
  admin.php

database/
  migrations/
  factories/
  seeders/

tests/
  Feature/
  Unit/
```

**Key conventions:**

- Controllers are thin: validate → call Service/Action → return Resource
- Tenant-scoped models include `tenant_id` and are filtered via tenant middleware + query helpers (do not rely on a header alone)
- All writes go through a Service or Action (consistent audit + events)
- API routes are versioned (`/api/v1/*`) and grouped under `routes/api_v1.php`

### 14A.2 Frontend (Vue)

```
resources/
  js/
    app.ts
    router/
      index.ts
      routes.app.ts
      routes.admin.ts
    api/
      client.ts
      errors.ts
      params.ts
    auth/
      useAuth.ts
      guards.ts
    tenancy/
      useTenant.ts
      tenantStore.ts
    layouts/
      AppLayout.vue
      AdminLayout.vue
      AuthLayout.vue
    components/
      ui/
        Button.vue
        Modal.vue
        Drawer.vue
        Dropdown.vue
        Toast.vue
        DataTable/
        Form/
      shared/
        PageHeader.vue
        Breadcrumbs.vue
        EmptyState.vue
        ConfirmDanger.vue
    pages/
      auth/
        Login.vue
        ForgotPassword.vue
        ResetPassword.vue
        VerifyEmail.vue
      app/
        Dashboard.vue
        Settings/
        Customers/
        Notes/
        Files/
        Imports/
        Notifications/
      admin/
        Dashboard.vue
        Tenants/
        Users/
        Jobs/
        Audit/
        Health/
    stores/
      notifications.ts
      ui.ts
    realtime/
      reverb.ts
      channels.ts
    styles/
      tailwind.css
```

**Frontend conventions:**

- Pages own data loading and layout composition
- Components in `ui/` are purely presentational and reusable
- Domain-specific components live next to pages under that domain
- API client + error parser is centralised in `resources/js/api`

## 14B. `make:saas-resource` Scaffolding Command Specification

### 14B.1 Command Overview

A single generator command that outputs a complete, consistent CRUD slice across backend, frontend, and tests.

**Command name:**

- `php artisan make:saas-resource <Name>`

**Examples:**

- `php artisan make:saas-resource Customer`
- `php artisan make:saas-resource Job --module=Customers --soft-deletes --files --notes`

### 14B.2 Inputs / Flags

**Required:**

- `Name` (Singular PascalCase) e.g. `Customer`

**Optional flags:**

- `--module=<ModuleName>` (default: inferred or `App`)
- `--table=<table_name>` (default: plural snake)
- `--tenant-scoped` (default: true)
- `--soft-deletes`
- `--policies` (default: true)
- `--factory` (default: true)
- `--seeder` (default: true)
- `--api-only` (skip Vue)
- `--ui` (default: true)
- `--realtime` (emit events + broadcast stubs)
- `--files` (attach file relation + UI widget)
- `--notes` (attach notes relation + UI widget)
- `--tags` (attach tags relation + UI widget)
- `--fields="name:string, status:enum(active|inactive), phone:string?"`

### 14B.3 Outputs (Backend)

Generates:

- Migration:
    - `id`, `tenant_id` (if tenant-scoped), timestamps
    - optional `deleted_at`
    - fields specified via `--fields`
- Model:
    - fillable guarded defaults
    - casts for enums/dates
    - tenant scope applied (if tenant-scoped)
- Policy:
    - `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`
- Form Requests:
    - `Store<Name>Request`, `Update<Name>Request`
- Controller:
    - REST endpoints + consistent responses
- Service:
    - `create`, `update`, `delete` methods
    - hooks for activity log and events
- Routes:
    - `GET /api/<resources>`
    - `GET /api/<resources>/{id}`
    - `POST /api/<resources>`
    - `PUT /api/<resources>/{id}`
    - `DELETE /api/<resources>/{id}`

### 14B.4 Outputs (Frontend)

Generates:

- `pages/<domain>/<Resource>/Index.vue`
- `pages/<domain>/<Resource>/Show.vue`
- `pages/<domain>/<Resource>/Create.vue`
- `pages/<domain>/<Resource>/Edit.vue`
- `components/<domain>/<Resource>/<Resource>Form.vue`
- `components/<domain>/<Resource>/<Resource>Table.vue`
- `api/<resource>.ts` client wrapper
- Routes added to `routes.app.ts` or module route file

**UI requirements:**

- Index: search, filters, sorting, pagination, bulk actions
- Form: reusable field components, server error mapping, dirty guard
- Show: activity timeline widget + related blocks (notes/files/tags if enabled)

### 14B.5 Outputs (Tests)

Pest feature tests:

- Auth required
- Tenant scoping enforced
- Policy enforcement for each action
- Validation errors for required fields
- Index list supports sort + search (basic assertions)

### 14B.6 Naming Rules

- Model: `Customer`
- Table: `customers`
- Route prefix: `/api/customers`
- Vue route name: `customers.index`, etc.
- Components: `CustomerForm`, `CustomerTable`

---

## 15. Baseline Modules Included

1. Users & Organisations
2. Customers (full CRUD)
3. Tags & Notes (polymorphic)
4. Files
5. Notifications
6. Imports

---

## Appendix A — Default Database Schema

> This is the baseline schema the template should ship with. Additional generated resources follow the same conventions.

### A0. Device Sessions & Refresh Tokens (Token-only Auth)

#### `device_sessions`

- `id`
- `user_id`
- `device_label` (string, nullable)
- `created_ip` (string, nullable)
- `ip_address` (string, nullable)
- `user_agent` (text, nullable)
- `last_used_at` (timestamp, nullable)
- `revoked_at` (timestamp, nullable)
- `revocation_reason` (string, nullable) _(e.g., logout, password_reset, refresh_reuse_detected, admin_revoked)_
- `created_at`, `updated_at`

#### `refresh_tokens`

- `id`
- `user_id`
- `device_session_id`
- `token_hash` (string, unique)
- `expires_at`
- `revoked_at` (nullable)
- `replaced_by_id` (nullable, self FK)
- `last_used_at` (nullable)
- `created_at`

### A1. Core / Auth

#### `users`

- `id` (bigint)
- `name` (string)
- `email` (string, unique)
- `email_verified_at` (timestamp, nullable)
- `password` (string)
- `two_factor_secret` (text, nullable)
- `two_factor_recovery_codes` (text, nullable)
- `is_super_admin` (boolean, default false)
- `locked_at` (timestamp, nullable)
- `last_login_at` (timestamp, nullable)
- `created_at`, `updated_at`

#### `personal_access_tokens` (Sanctum)

- standard Sanctum schema

#### `user_sessions` (optional but recommended)

- `id`
- `user_id`
- `ip_address`
- `user_agent`
- `last_activity_at`
- `created_at`, `updated_at`

#### `login_events` (optional)

- `id`
- `user_id`
- `type` (enum: login_success, login_failed, logout)
- `ip_address`
- `user_agent`
- `meta` (json)
- `created_at`

### A2. Tenancy

#### `tenants`

- `id`
- `name`
- `slug` (string, unique)
- `status` (enum: active, disabled, deleted)
- `plan` (string, nullable)
- `trial_ends_at` (timestamp, nullable)
- `disabled_at` (timestamp, nullable)
- `deleted_at` (timestamp, nullable) _(soft delete)_
- `created_at`, `updated_at`

#### `tenant_user`

- `id`
- `tenant_id`
- `user_id`
- `role` (enum: owner, admin, member, readonly)
- `joined_at` (timestamp)
- unique index: (`tenant_id`, `user_id`)

#### `tenant_invitations`

- `id`
- `tenant_id`
- `email`
- `role` (enum)
- `token` (string, unique)
- `invited_by_user_id`
- `expires_at`
- `accepted_at` (nullable)
- `created_at`, `updated_at`

### A3. Activity & Notifications

#### `activity_log`

- `id`
- `tenant_id` (nullable for platform-level events)
- `actor_user_id` (nullable)
- `impersonator_user_id` (nullable)
- `action` (string)
- `subject_type` (string)
- `subject_id` (string/bigint)
- `before` (json, nullable)
- `after` (json, nullable)
- `meta` (json, nullable)
- `ip_address` (nullable)
- `user_agent` (nullable)
- `request_id` (nullable)
- `created_at`

#### `notifications`

- Laravel database notifications schema

#### `notification_preferences`

- `id`
- `tenant_id`
- `user_id`
- `key` (string)
- `channels` (json) _(e.g., {"in_app": true, "email": false})_
- `created_at`, `updated_at`

### A4. Support / Admin

#### `admin_notes`

- `id`
- `target_type` (enum: user, tenant)
- `target_id`
- `note` (text)
- `created_by_user_id`
- `created_at`, `updated_at`

#### `support_cases` (lightweight)

- `id`
- `tenant_id` (nullable)
- `user_id` (nullable)
- `subject` (string)
- `status` (enum: open, pending, resolved)
- `priority` (enum: low, medium, high)
- `meta` (json)
- `created_by_user_id`
- `created_at`, `updated_at`

### A4A. Generic Webhook Events Log (Reusable)

Use for Stripe, Twilio, Vapi, etc.

#### `webhook_events`

- `id`
- `provider` (string) _(stripe, twilio, vapi, etc.)_
- `event_id` (string, unique per provider)
- `type` (string)
- `payload` (json)
- `received_at` (timestamp)
- `processed_at` (timestamp, nullable)
- `status` (enum: received, processed, failed)
- `attempts` (int)
- `last_error` (text, nullable)
- `idempotency_key` (string, nullable)

**Processing rules:**

- Verify signature (provider-specific)
- Dedupe by (provider, event*id) *(unique index)\_
- Process via queue
- Safe retries

### A5. Feature Flags

#### `feature_flags`

- `id`
- `key` (string, unique)
- `description` (string, nullable)
- `default_enabled` (boolean)
- `created_at`, `updated_at`

#### `feature_flag_overrides`

- `id`
- `flag_id`
- `scope_type` (enum: tenant, user)
- `scope_id`
- `enabled` (boolean)
- `created_by_user_id`
- `created_at`, `updated_at`

### A6. Files & Notes (Polymorphic)

#### Files requirements (Security + UX)

- Signed temporary URLs for downloads (short TTL)
- Signed temporary URLs for uploads (when using S3 later)
- Storage abstraction (local now, S3-compatible later)
- Optional virus scanning hook (async): quarantine until scan passes

#### `files`

- `id`
- `tenant_id`
- `disk` (string)
- `path` (string)
- `original_name` (string)
- `mime_type` (string)
- `size_bytes` (bigint)
- `uploaded_by_user_id`
- `created_at`, `updated_at`

#### `fileables`

- `id`
- `tenant_id`
- `file_id`
- `fileable_type`
- `fileable_id`
- `created_at`

#### `notes`

- `id`
- `tenant_id`
- `author_user_id`
- `body` (text)
- `noteable_type`
- `noteable_id`
- `created_at`, `updated_at`

### A7. Tags (optional but recommended)

#### `tags`

- `id`
- `tenant_id`
- `name`
- `color` (string, nullable)
- `created_at`, `updated_at`

#### `taggables`

- `id`
- `tenant_id`
- `tag_id`
- `taggable_type`
- `taggable_id`
- `created_at`

### A8. Imports

#### `imports`

- `id`
- `tenant_id`
- `type` (string)
- `status` (enum: queued, running, completed, failed)
- `progress` (integer 0-100)
- `total_rows` (integer)
- `processed_rows` (integer)
- `error_count` (integer)
- `input_file_id` (nullable)
- `result_file_id` (nullable)
- `meta` (json)
- `created_by_user_id`
- `created_at`, `updated_at`

---

## Appendix B — Tenancy & Query Conventions

### B1. Tenant Scoping Rules

- Any tenant-scoped table **must** include `tenant_id`
- Any tenant-scoped model **must** apply tenant scope in queries
- Any route that reads/writes tenant data **must** run behind tenant middleware

### B2. IDs

- Default: bigint IDs are acceptable
- Optional: support UUID/ULID (template can include an easy switch)

### B3. List Endpoints

All index endpoints accept:

- `page`
- `per_page`
- `sort` (e.g. `name` or `-created_at`)
- `search`
- `filters[...]` (key/value)

---

## Appendix C — UI Standard Patterns

### C1. Page Layout

- `PageHeader` with title, subtitle, primary action, secondary actions
- Breadcrumbs
- Content sections as cards

### C2. Tables

- Column toggles
- Saved views
- Bulk actions

### C3. Forms

- Field components
- Inline errors
- Dirty guard

---

## Guiding Principle

> Every entity is tenant-scoped, policy-protected, listable, editable, audited, test-covered, and production-safe by default.

---

**This spec is intended to be used as a long-term foundation for multiple SaaS products.**

> Every entity is tenant-scoped, policy-protected, listable, editable, audited, test-covered, and production-safe by default.

---

**This spec is intended to be used as a long-term foundation for multiple SaaS products.**
