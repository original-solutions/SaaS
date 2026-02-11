# Laravel SaaS Base Template — Implementation Plan (TDD)

**Version:** v3
**Date:** 2026-02-11
**Spec:** [docs/spec.md](spec.md)

---

## Overview

Build a production-grade SaaS foundation from a stock Laravel 12 install per the spec. The codebase currently has zero custom code — every model, migration, route, policy, service, and Vue component must be created. The plan is organized into 14 sequenced phases, backend-first, with tests written before implementation at every step. MySQL is the test DB for production parity. TypeScript is used throughout the frontend.

Each phase follows the cycle: **write Pest tests → implement to green → run `vendor/bin/pint --dirty` → verify.**

### Key Packages

- **spatie/laravel-permission v6** — RBAC engine. Teams feature with `tenant_id` as `team_foreign_key`. Replaces a custom role hierarchy. Extension to granular permissions requires zero schema changes.
- **spatie/laravel-activitylog v4** — Audit engine. Custom `Activity` model adds `tenant_id`, `impersonator_user_id`, `request_id`, `ip_address`, `user_agent`. Spec's `before`/`after` columns map to `properties->old` / `properties->attributes`. `LogsActivity` trait on all models for auto-logging.

### Key Integration Decisions

- **spatie/laravel-permission**: Teams feature enabled with `team_foreign_key` set to `tenant_id`. Roles (`owner`, `admin`, `member`, `readonly`) are team-scoped. `super-admin` is a global role (`tenant_id = null`). The `tenant_user` pivot retains a `role` column (spec compliance) synced with Spatie roles via the service layer. Spatie's `hasRole()` is the authoritative check in all policies.
- **spatie/laravel-activitylog**: Custom `Activity` model extends Spatie's, adding `tenant_id`, `impersonator_user_id`, `request_id`, `ip_address`, `user_agent` columns. Auto-filled via `Activity::creating()`. The spec's `before`/`after` snapshots map to Spatie's `properties->old` / `properties->attributes` (handled by `LogsActivity` trait). All tenant-scoped models use `LogsActivity` for automatic create/update/delete auditing.

### Change Markers

- **[NEW]** — was missing from v1/v2, now added
- **[SPATIE]** — involves Spatie package integration
- **[PROMOTED]** — was implicitly covered, now an explicit task

---

## Phase 1: Foundation & Tooling Setup

**Goal:** Working project skeleton with all dependencies, folder structure, config, and test harness.

### Steps

1. **Install backend packages** — `laravel/sanctum`, `laravel/reverb`, **[SPATIE]** `spatie/laravel-permission`, **[SPATIE]** `spatie/laravel-activitylog` via Composer.
2. **Install frontend packages** — `vue@3`, `vue-router@4`, `pinia`, `@vitejs/plugin-vue`, `typescript`, `vue-tsc`, `shadcn-vue`, `radix-vue`, `@tanstack/vue-table`, `vee-validate`, `zod`, `lucide-vue-next`, `prettier`, `eslint`, `eslint-plugin-vue`, `@typescript-eslint/parser`. Convert `resources/js/app.js` → `app.ts`.
3. **Configure Vite** — Add Vue plugin + TypeScript support in `vite.config.js`. Add `tsconfig.json`.
4. **[SPATIE] Publish and configure Spatie configs:**
   - `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` → creates `config/permission.php`. Set `'teams' => true`, `'team_foreign_key' => 'tenant_id'`. Set guard to `api` (Sanctum).
   - `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"` → creates `config/activitylog.php`. Set `'activity_model' => \App\Models\Activity::class`, configure `delete_records_older_than_days`.
5. **Create backend folder structure** per spec §14A.1 — directories under `app/` for `Actions/`, `Services/`, `DTO/`, `Enums/`, `Support/Auth/`, `Support/Tenancy/`, `Support/Impersonation/`, `Support/Query/`, `Events/`, `Listeners/`, `Notifications/`, `Jobs/`, `Rules/`, `Policies/`, `Http/Middleware/`, `Http/Requests/Api/V1/`, `Http/Controllers/Api/V1/`, `Http/Controllers/Admin/`.
6. **Create frontend folder structure** per spec §14A.2 — `resources/js/router/`, `api/`, `auth/`, `tenancy/`, `layouts/`, `components/ui/`, `components/shared/`, `pages/auth/`, `pages/app/`, `pages/admin/`, `stores/`, `realtime/`.
7. **Create route files** — `routes/api_v1.php` and `routes/admin.php`. Register in `bootstrap/app.php` with `/api/v1` prefix and `/admin` prefix respectively.
8. **Configure phpunit.xml** — Switch from SQLite to MySQL test database. Update `.env.example` with all SaaS-specific variables.
9. **Create test helpers** in `tests/Pest.php` — `actingAsTenantOwner()`, `actingAsMember()`, `actingAsSuperAdmin()`, `actingAsReadOnly()`. Enable `RefreshDatabase`. **[SPATIE]** Helpers use `$user->assignRole('owner')` (team-scoped) to set roles via Spatie.
10. **Configure Sanctum** — Publish config, set up API guard in `config/auth.php`.
11. **Prettier + ESLint + Pint** — Config files (`.prettierrc`, `.eslintrc.cjs`, `pint.json` if needed). Pre-commit hooks via `husky` + `lint-staged`. **[NEW]** Add ESLint rule to disallow `eval`, `Function` constructors, and `v-html` usage (enforcing §2.3.1.X9-X11).
12. **Security headers middleware** — Create `SecurityHeaders` middleware adding CSP (nonce-based, no `unsafe-inline`), HSTS, X-Content-Type-Options, Referrer-Policy. Register in `bootstrap/app.php`.
13. **CORS config** — Update/publish `config/cors.php` for SPA usage without cookies.
14. **Base SPA entry point** — `resources/views/app.blade.php` Blade file that boots Vue app with CSP nonce. Update `routes/web.php` to serve SPA for all non-API routes.
15. **[NEW] UUID/ULID easy-switch support** (§B2) — Create a `HasConfigurableId` trait in `app/Support/` that reads from `config('saas.id_strategy')` (default: `bigint`, options: `uuid`, `ulid`). Model stubs and migrations use this trait so switching ID strategy is a one-config change. Document in `config/saas.php`.
16. **[NEW] Tenant identification strategy decision** (§3.1.1.S5) — Document in `config/saas.php` that the template uses **Header-only (`X-Tenant-ID`)** as the tenant identification strategy, with a comment explaining when to switch to subdomain or both.

### Tests (~6)

- Health endpoint (`GET /up`) returns 200.
- API v1 route group returns 404 for unknown routes under `/api/v1`.
- Security headers present on responses (CSP, HSTS, X-Content-Type-Options, Referrer-Policy).
- CORS headers present for API requests.
- **[NEW]** Config `saas.id_strategy` defaults to `bigint`.
- **[NEW]** Config `saas.tenant_identification` is `header`.

---

## Phase 2: User Model, Authentication & Device Sessions

**Goal:** Complete auth system including 2FA, magic links, session management API, token management API.

### Enums

- `TenantRole` (Owner, Admin, Member, Readonly) → `app/Enums/TenantRole.php`
- `TenantStatus` (Active, Disabled, Deleted) → `app/Enums/TenantStatus.php`
- `LoginEventType` (LoginSuccess, LoginFailed, Logout) → `app/Enums/LoginEventType.php`
- `RevocationReason` (Logout, PasswordReset, RefreshReuseDetected, AdminRevoked) → `app/Enums/RevocationReason.php`

### Migrations

1. Alter `users` table — add `two_factor_secret`, `two_factor_recovery_codes`, `is_super_admin`, `locked_at`, `last_login_at`.
2. Create `device_sessions` table — all columns per §A0.DS including **[PROMOTED]** `created_ip` (string, nullable) and `revocation_reason` (string, nullable) from Appendix A0.
3. Create `refresh_tokens` table — all columns per §A0.RT.
4. Create `login_events` table — per §A1.LE.
5. Create `personal_access_tokens` table (Sanctum publish migration).
6. **[NEW]** Create `user_sessions` table (§A1, recommended) — `id`, `user_id`, `ip_address`, `user_agent`, `last_activity_at`, `created_at`, `updated_at`.
7. **[NEW]** Create `magic_login_tokens` table — `id`, `user_id`, `token_hash` (string, unique), `expires_at`, `used_at` (nullable), `created_at`. Behind feature flag `magic_link_login`.

### Models

- Enhance `User` model — new fields, casts, `HasApiTokens` trait, **[SPATIE]** `HasRoles` trait, **[SPATIE]** `CausesActivity` trait. Relationships to `DeviceSession`, `RefreshToken`, `LoginEvent`, `UserSession`. Methods: `isLocked()`, `isSuperAdmin()`.
- `DeviceSession` model — relationships, scopes (`active`, `revoked`), `revoke(RevocationReason)` method.
- `RefreshToken` model — relationships, scopes, `isExpired()`, `isRevoked()`, `isUsed()`.
- `LoginEvent` model.
- **[NEW]** `UserSession` model.
- **[NEW]** `MagicLoginToken` model.

### Factories

- Update `UserFactory` — add states: `superAdmin()`, `locked()`, `unverified()` (exists), `withTwoFactor()`.
- `DeviceSessionFactory`, `RefreshTokenFactory`, `LoginEventFactory`.

### Services

- `AuthService` (`app/Services/AuthService.php`) — `login()`, `refresh()`, `logout()`, `revokeAllForUser()`, `revokeDeviceSession()`. Handles token creation, rotation, reuse detection. **[SPATIE]** Reuse detection creates audit entry via `activity()->event('refresh_token_reuse_detected')->causedBy($user)->withProperties([...])->log(...)` on the custom `Activity` model AND dispatches an `AdminSecurityAlert` notification to all users with Spatie's global `super-admin` role.
- **[PROMOTED]** Password change method explicitly revokes all tokens and device sessions with `RevocationReason::PasswordReset` — covers both password reset AND voluntary password change.
- **[NEW]** `TwoFactorService` (`app/Services/TwoFactorService.php`) — `enable()` (generate secret + show QR), `verify()` (confirm TOTP code to activate), `disable()` (require password confirmation), `generateRecoveryCodes()`, `verifyRecoveryCode()`. Uses TOTP library (e.g., `pragmarx/google2fa`).
- **[NEW]** `MagicLinkService` (`app/Services/MagicLinkService.php`) — `generate(email)` (creates token, sends signed email), `verify(token)` (validates, logs in, creates device session). Behind feature flag.
- **[NEW]** `DeviceSessionService` (`app/Services/DeviceSessionService.php`) — `listForUser()`, `revokeSession(id)`, `revokeAllExceptCurrent()`. Powers session management API.
- **[NEW]** `PersonalAccessTokenService` — `list()`, `create(name, abilities)`, `revoke(id)`. CRUD for Sanctum PATs.

### Controllers

- `AuthController` (`app/Http/Controllers/Api/V1/AuthController.php`) — `login`, `refresh`, `logout`, `me`.
- `PasswordResetController` — forgot password, reset password (revoke tokens on reset).
- `EmailVerificationController` — send verification, verify.
- **[NEW]** `TwoFactorController` — `enable`, `confirm`, `disable`, `generateRecoveryCodes`, login with 2FA challenge flow.
- **[NEW]** `MagicLinkController` — `request` (send magic link), `verify` (consume token, login).
- **[NEW]** `DeviceSessionController` — `index` (list user's device sessions), `destroy` (revoke specific session), `destroyAll` (revoke all except current).
- **[NEW]** `PersonalAccessTokenController` — `index`, `store`, `destroy`.

### Form Requests

- `LoginRequest`, `RefreshTokenRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`.
- **[NEW]** `EnableTwoFactorRequest`, `ConfirmTwoFactorRequest`, `DisableTwoFactorRequest`.
- **[NEW]** `RequestMagicLinkRequest`, `VerifyMagicLinkRequest`.
- **[NEW]** `CreatePersonalAccessTokenRequest`.

### Routes (in `routes/api_v1.php`)

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout` (auth required)
- `GET /api/v1/me` (auth required)
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `POST /api/v1/auth/email/verify/{id}/{hash}`
- `POST /api/v1/auth/email/resend`
- **[NEW]** `POST /api/v1/auth/two-factor/enable`
- **[NEW]** `POST /api/v1/auth/two-factor/confirm`
- **[NEW]** `DELETE /api/v1/auth/two-factor`
- **[NEW]** `POST /api/v1/auth/two-factor/recovery-codes`
- **[NEW]** `POST /api/v1/auth/magic-link`
- **[NEW]** `POST /api/v1/auth/magic-link/verify`
- **[NEW]** `GET /api/v1/account/sessions` (list device sessions)
- **[NEW]** `DELETE /api/v1/account/sessions/{session}` (revoke session)
- **[NEW]** `DELETE /api/v1/account/sessions` (revoke all except current)
- **[NEW]** `GET /api/v1/account/tokens` (list PATs)
- **[NEW]** `POST /api/v1/account/tokens` (create PAT)
- **[NEW]** `DELETE /api/v1/account/tokens/{token}` (revoke PAT)
- **[NEW]** `PUT /api/v1/account/password` (change password — revokes all tokens)

### Config

`config/saas.php`:
- `access_token_ttl` (15 min)
- `refresh_token_ttl` (7 days)
- `revoke_all_on_reuse` (bool)
- `invite_expiry_days` (7)
- **[NEW]** `magic_link_ttl` (15 min)
- **[NEW]** `impersonation_ttl` (60 min)
- **[NEW]** `grace_period_days` (7)
- **[NEW]** `two_factor_enabled` (bool)

### Tests (~70)

- `tests/Feature/Auth/LoginTest.php` — successful login returns `{access_token, refresh_token, expires_in}`, invalid credentials 401, locked user cannot login, unverified user handling, login creates device session + login_event, `last_login_at` updated, **[NEW]** login with 2FA enabled requires TOTP challenge, **[NEW]** login with 2FA using recovery code.
- `tests/Feature/Auth/RefreshTokenTest.php` — successful refresh rotates token, old token invalidated, expired token rejected, **[PROMOTED]** revoked/used token triggers: (a) entire session chain revoked, (b) `refresh_token_reuse_detected` activity log entry created, (c) admin notification dispatched, (d) config-driven revoke-all-sessions. Refresh returns new tokens, refresh updates `last_used_at`.
- `tests/Feature/Auth/LogoutTest.php` — logout revokes refresh token + access tokens, records login_event.
- `tests/Feature/Auth/MeTest.php` — authenticated user gets profile, unauthenticated 401.
- `tests/Feature/Auth/PasswordResetTest.php` — forgot password sends email, reset works, **[PROMOTED]** reset revokes ALL tokens and device sessions with `RevocationReason::PasswordReset`.
- `tests/Feature/Auth/PasswordChangeTest.php` — **[PROMOTED]** voluntary password change revokes all tokens/device sessions.
- `tests/Feature/Auth/EmailVerificationTest.php` — verification email sent, valid link works, already verified handling.
- **[NEW]** `tests/Feature/Auth/TwoFactorTest.php` — enable generates secret + recovery codes, confirm activates 2FA, login requires TOTP after enable, invalid TOTP rejected, recovery code works (one-time use), disable requires password confirmation, regenerate recovery codes.
- **[NEW]** `tests/Feature/Auth/MagicLinkTest.php` — request sends email with signed link, valid token logs in + creates device session, expired token rejected, used token rejected, feature flag controls availability.
- **[NEW]** `tests/Feature/Account/DeviceSessionTest.php` — list shows all user sessions, revoke specific session, revoke all except current, revoked session's refresh tokens are invalidated.
- **[NEW]** `tests/Feature/Account/PersonalAccessTokenTest.php` — list tokens, create token returns plain text once, revoke token, token authenticates API requests.
- `tests/Unit/Models/DeviceSessionTest.php` — scopes, revoke method, **[PROMOTED]** `revocation_reason` stored correctly.
- `tests/Unit/Models/RefreshTokenTest.php` — `isExpired()`, `isRevoked()`, `isUsed()`.
- `tests/Unit/Services/AuthServiceTest.php` — reuse detection full chain.

---

## Phase 3: Multi-Tenancy Core

**Goal:** Tenant model, pivot, middleware, context resolution, scoping, Spatie roles seeded.

### Migrations

1. Create `tenants` table — per §A2.T (with soft deletes). Includes `slug` (unique), `status` (enum), `plan` (nullable), `trial_ends_at`, `disabled_at`.
2. Create `tenant_user` pivot — per §A2.TU (unique index on `tenant_id`, `user_id`). **[SPATIE]** Retains `role` column for spec compliance and query convenience. This column is synced with Spatie's `model_has_roles` (team-scoped) via the service layer.
3. **[SPATIE]** Spatie permission tables — publish migration creates `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`. The `roles` and pivot tables use `tenant_id` as the team foreign key.

### Models

- `Tenant` — fillable, casts (`status` → `TenantStatus` enum), soft deletes, relationships (`users()` belongsToMany with pivot `role`/`joined_at`). Methods: `isActive()`, `isDisabled()`.
- Update `User` — add `tenants()` belongsToMany relationship.

### Factories

- `TenantFactory` — states: `disabled()`, `deleted()`, `trialing()`.

### Middleware

- `ResolveTenant` (`app/Http/Middleware/ResolveTenant.php`) — validates `X-Tenant-ID` header present → loads tenant → confirms user membership via `tenant_user` → tenant must be `active` (Super Admin bypasses for Admin area) → sets tenant in context. Returns 400 with `{"error": "TENANT_REQUIRED"}` when header missing on tenant-scoped routes. **[SPATIE]** Calls `app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->id)` so all subsequent `hasRole()` / `hasPermissionTo()` checks are scoped to this tenant. Also unsets cached role/permission relations on the user.

### Support Classes

- `TenantContext` (`app/Support/Tenancy/TenantContext.php`) — singleton to hold current tenant.
- `BelongsToTenant` trait (`app/Support/Tenancy/BelongsToTenant.php`) — auto-applies tenant scope on boot, auto-sets `tenant_id` on creating.
- `TenantScope` (`app/Support/Tenancy/TenantScope.php`) — global scope applying `where tenant_id = ?`.

### [SPATIE] Role Seeder

- Create global `super-admin` role (`tenant_id = null`).
- Create team-scoped roles: `owner`, `admin`, `member`, `readonly`. Created with `tenant_id = null` as "template" roles (global roles apply across all teams).
- **Membership service pattern:** When attaching a user to a tenant, the service: (a) creates `tenant_user` pivot entry with `role` column, (b) calls `$user->assignRole($roleName)` which stores in `model_has_roles` with the current `tenant_id` context. Both are kept in sync.

### Seeder

- Demo tenant ("Acme Corp") with Owner + test users. Super Admin user. **[SPATIE]** Super Admin gets global `super-admin` role. Tenant Owner gets team-scoped `owner` role.

### Routes

- `GET /api/v1/tenants` (list user's tenants — NO tenant header required)
- `POST /api/v1/tenants` (create tenant)
- `GET /api/v1/tenants/{tenant}` (tenant detail)
- `PUT /api/v1/tenants/{tenant}` (update tenant)
- `DELETE /api/v1/tenants/{tenant}` (soft delete)

### Tests (~35)

- `tests/Feature/Tenancy/ResolveTenantMiddlewareTest.php` — missing header returns 400 `TENANT_REQUIRED`, missing header allows auth/me/tenants endpoints, invalid tenant ID 404, user not member 403, **[PROMOTED]** disabled tenant rejected (unless Super Admin), active tenant resolves, tenant set in context.
- `tests/Feature/Tenancy/TenantScopingTest.php` — `BelongsToTenant` auto-scopes queries, auto-sets `tenant_id` on create, cross-tenant data inaccessible.
- `tests/Feature/Tenancy/TenantCrudTest.php` — create, list, view, update, soft delete, user becomes Owner on creation.
- **[SPATIE]** `tests/Feature/Tenancy/SpatieRoleSyncTest.php` — adding user to tenant assigns Spatie role scoped to that tenant, user has `owner` in tenant A but `member` in tenant B, removing membership revokes tenant-scoped role, `tenant_user.role` stays in sync with Spatie role.
- `tests/Unit/Support/TenantContextTest.php` — set/get.
- `tests/Unit/Models/TenantTest.php` — relationships, scopes, `isActive()`, `isDisabled()`.

---

## Phase 4: Authorization & Roles

**Goal:** Policies and gates using Spatie's permission system, with documented extension to granular permissions.

### [SPATIE] Replaces Custom RoleHierarchy

Spatie IS the authorization engine.

### Policies

- `TenantPolicy` — uses `$user->hasRole('owner')` and `$user->hasRole(['owner', 'admin'])` for checks (automatically scoped to current tenant via middleware). Example:
  - `update`: `$user->hasRole(['owner', 'admin'])`
  - `delete`: `$user->hasRole('owner')`
  - `viewAny`/`view`: `$user->hasAnyRole(['owner', 'admin', 'member', 'readonly'])`
  - `create`: `$user->hasAnyRole(['owner', 'admin', 'member'])`

### Gates

- `is-super-admin`: `$user->hasRole('super-admin')` (global role, no team scope needed).
- Register a `Gate::before()` callback: if user has `super-admin` role, return true for all gates (except impersonation guards).

### [SPATIE] RBAC Extension Path (§4.1)

Document in `config/permission.php` comments AND `config/saas.php`:
1. Current setup uses roles only.
2. To add granular permissions: create `Permission` records (e.g., `customers.create`, `customers.delete`).
3. Assign permissions to roles via `$role->givePermissionTo('customers.create')`.
4. Change policy checks from `$user->hasRole('owner')` to `$user->hasPermissionTo('customers.delete')`.
5. Spatie handles role→permission resolution automatically.
6. **Zero schema change** — `permissions` and `role_has_permissions` tables already exist.

### Tests (~22)

- `tests/Feature/Authorization/TenantPolicyTest.php` — Owner can update/delete, Admin can update not delete, Member can read/create, Read-only can only read. **[SPATIE]** All checks use Spatie's `hasRole()` under the hood.
- **[SPATIE]** `tests/Feature/Authorization/PermissionExtensionTest.php` — Create a permission, assign to role, verify `hasPermissionTo()` works. Demonstrates the extension path is functional without schema changes.
- `tests/Feature/Authorization/GateTest.php` — Super Admin bypasses all gates via `Gate::before()`. Non-super-admin does not bypass.

---

## Phase 5: Invitations & User Account Lifecycle

**Goal:** Full invite flow with all edge cases, user lifecycle. Membership creation syncs Spatie roles.

### Migrations

1. Create `tenant_invitations` table — per §A2.TI.

### Models

- `TenantInvitation` — fillable, casts, relationships, `isExpired()`, `isPending()`.

### Factories

- `TenantInvitationFactory` — states: `expired()`, `accepted()`.

### Services

- `InvitationService` (`app/Services/InvitationService.php`) — `invite()` (creates or resends if duplicate pending exists), `accept()` (validates email match, verified email required, attaches membership), `decline()`, `updateRole()`. **[NEW]** Accept endpoint returns explicit `INVITE_EMAIL_MISMATCH` error code when logged-in user's email doesn't match the invite, along with `expected_email` (masked) so frontend can render "switch account" / "log out and continue" flow. Uses **signed URLs** for invite links. **[SPATIE]** On `accept()`, creates `tenant_user` pivot AND assigns Spatie role: `setPermissionsTeamId($tenant->id)` → `$user->assignRole($invitation->role)`.
- `UserAccountService` (`app/Services/UserAccountService.php`) — `exportData()`, `deleteAccount()` (blocks if sole owner, removes memberships, revokes all tokens/device sessions, **[SPATIE]** calls `$user->roles()->detach()` to remove all Spatie role assignments), `changeEmail()` (re-verify + **[PROMOTED]** revoke tokens), `lock()`, `unlock()`. **[NEW]** `deleteAccount()` implements authored content retention policy configurable via `config('saas.content_retention_on_delete')` with values `anonymise` or `retain`.

### Controllers

- `InvitationController` — send invite, accept, decline, resend, update role.
- `UserAccountController` — export data, delete account, change email.

### Form Requests

- `SendInvitationRequest`, `AcceptInvitationRequest`, `UpdateInvitationRoleRequest`, `ChangeEmailRequest`, `DeleteAccountRequest`.

### Routes

- `POST /api/v1/tenants/{tenant}/invitations`
- `GET /api/v1/invitations/{token}` (view invite details)
- `POST /api/v1/invitations/{token}/accept`
- `POST /api/v1/invitations/{token}/decline`
- `POST /api/v1/tenants/{tenant}/invitations/{invitation}/resend`
- `PUT /api/v1/tenants/{tenant}/invitations/{invitation}` (update role)
- `GET /api/v1/account/export`
- `DELETE /api/v1/account`
- `PUT /api/v1/account/email`

### Tests (~42)

- `tests/Feature/Invitations/SendInvitationTest.php` — Owner/Admin can invite, Member/Read-only cannot, invite creates record, invite sends email with **signed link**, **[PROMOTED]** duplicate pending invite for same (tenant, email) resends instead of creating new record, role is set.
- `tests/Feature/Invitations/AcceptInvitationTest.php` — valid token accepted, expired rejected, already-accepted rejected, accepting attaches membership with correct role, **[NEW]** logged-in user with different email returns `INVITE_EMAIL_MISMATCH` error with masked `expected_email`, **[PROMOTED]** verified email required before accepting, existing user gets membership attached, new user register-then-accept flow. **[SPATIE]** Verify `accept()` assigns Spatie role scoped to tenant.
- `tests/Feature/Invitations/ResendInvitationTest.php` — resend generates new token + invalidates old, rate limiting enforced.
- `tests/Feature/Invitations/UpdateInvitationRoleTest.php` — Owner/Admin can change role on pending invite.
- `tests/Feature/UserAccount/ExportDataTest.php` — returns profile + memberships + audit events.
- `tests/Feature/UserAccount/DeleteAccountTest.php` — **[PROMOTED]** sole Owner blocked, non-sole Owner deleted, memberships removed, tokens revoked, device sessions revoked, **[NEW]** `anonymise` config nullifies actor fields, `retain` config keeps `user_id` intact. **[SPATIE]** Verify `deleteAccount()` removes all Spatie roles.
- `tests/Feature/UserAccount/ChangeEmailTest.php` — email changed, re-verification triggered, **[PROMOTED]** tokens revoked.
- `tests/Feature/UserAccount/LockUnlockTest.php` — admin can lock/unlock, locked user cannot login.

---

## Phase 6: Activity Log, Notifications & Feature Flags

**Goal:** Spatie activitylog with custom columns, notifications, feature flags.

### [SPATIE] Migrations

1. **Activity log** — publish Spatie's migration, customise to add: `event` (string, nullable), `batch_uuid` (uuid, nullable, indexed), `tenant_id` (unsignedBigInteger, nullable, indexed), `impersonator_user_id` (unsignedBigInteger, nullable), `request_id` (uuid, nullable, indexed), `ip_address` (string(45), nullable), `user_agent` (text, nullable). Spatie's base columns remain as-is.
   - **Schema mapping to spec §A3.AL:** `causer_id` = spec's `actor_user_id`, `event` = spec's `action`, `properties->old` = spec's `before`, `properties->attributes` = spec's `after`, additional `properties` keys = spec's `meta`. Custom columns cover `tenant_id`, `impersonator_user_id`, `ip_address`, `user_agent`, `request_id`.
2. `notifications` table — Laravel database notifications migration.
3. `notification_preferences` table — per §A3.NP.
4. `feature_flags` table — per §A5.FF.
5. `feature_flag_overrides` table — per §A5.FFO.

### [SPATIE] Custom Activity Model

`app/Models/Activity.php`:
- Extends `Spatie\Activitylog\Models\Activity`.
- `booted()` method with `creating` callback that auto-fills:
  - `tenant_id` from `TenantContext`
  - `ip_address` from `request()->ip()`
  - `user_agent` from `request()->userAgent()`
  - `request_id` from current correlation ID
  - `impersonator_user_id` from impersonation context
- Tenant global scope that filters by current tenant when context is set (allows platform-level logs when no tenant).
- Relationships: `tenant()`, `impersonator()`.
- PII redaction: `scopeRedacted()` that strips sensitive fields from `properties` JSON based on configurable denylist.
- Registered in `config/activitylog.php`.

### [SPATIE] LogsActivity on Models

All tenant-scoped models use `LogsActivity` trait with `getActivitylogOptions()`:
- `LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs()`
- `tapActivity()` method to set `tenant_id` from model's own `tenant_id`.

### [SPATIE] ActivityLogService

`app/Services/ActivityLogService.php`:
- **Thin wrapper** around Spatie's `activity()` helper for manual logging.
- Methods: `log(string $event, ?Model $subject, array $properties = [])`.
- Auto-resolves causer, handles impersonator context, applies PII redaction.
- Retention: uses Spatie's `delete_records_older_than_days` config as default. Override per-tenant.
- **[NEW]** Post-deletion retention: billing/compliance logs (where `log_name` = `billing` or `compliance`) excluded from cleanup. Documented in config.

### Other Models

- `NotificationPreference`, `FeatureFlag`, `FeatureFlagOverride`.

### Services

- `FeatureFlagService` — `isEnabled(key, ?tenant, ?user)` with override precedence: user > tenant > default.

### Controllers

- `NotificationController` — list, mark read, mark all read, preferences CRUD.
- `FeatureFlagController` (admin) — list, create, update, manage overrides.

### Tests (~35)

- **[SPATIE]** `tests/Feature/ActivityLog/SpatieActivityLogTest.php` — creating model with `LogsActivity` auto-logs with `event=created`, properties contain `attributes`, updating logs with `old` and `attributes`, deleting logs `event=deleted`, `causer_id` set to authenticated user, `tenant_id` auto-filled, `ip_address` auto-filled, `request_id` auto-filled, `impersonator_user_id` filled during impersonation.
- **[SPATIE]** `tests/Feature/ActivityLog/ActivityLogRetentionTest.php` — old logs pruned per config, billing/compliance logs survive tenant deletion, non-billing logs deleted on hard-delete.
- **[SPATIE]** `tests/Feature/ActivityLog/PiiRedactionTest.php` — sensitive fields stripped from properties.
- `tests/Feature/Notifications/NotificationTest.php` — database notifications, read/unread toggle, list paginated, preferences respected.
- `tests/Feature/FeatureFlags/FeatureFlagServiceTest.php` — default value, tenant override, user override precedence, disabled flag.
- `tests/Feature/FeatureFlags/FeatureFlagCrudTest.php` — Super Admin CRUD flags, create override.

---

## Phase 7: Files, Notes, Tags & Imports

**Goal:** Polymorphic relations with auto audit logging via Spatie.

### Migrations

1. `files` table — per §A6.F.
2. `fileables` table — per §A6.FA.
3. `notes` table — per §A6.N.
4. `tags` table — per §A7.T.
5. `taggables` table — per §A7.TA.
6. `imports` table — per §A8.I.

### Enums

- `ImportStatus` (Queued, Running, Completed, Failed).

### Models

- `File` — tenant-scoped, `BelongsToTenant`, **[SPATIE]** `LogsActivity` trait.
- `Note` — tenant-scoped, polymorphic (`noteable`), **[SPATIE]** `LogsActivity`.
- `Tag` — tenant-scoped, polymorphic (`taggables`), **[SPATIE]** `LogsActivity`.
- `Import` — tenant-scoped, **[SPATIE]** `LogsActivity` (logs status transitions).

### Traits

- `HasFiles` — polymorphic `files()` via `fileables`.
- `HasNotes` — polymorphic `notes()`.
- `HasTags` — polymorphic `tags()` via `taggables`.

### Services

- `FileService` — `upload()`, `download()` (signed temp URL, short TTL), **[NEW]** `generateUploadUrl()` (presigned URL for S3 direct upload — stubbed for local), `delete()`. Storage abstraction (local/S3). Virus scan hook.
- `ImportService` — `create()`, `process()`, `updateProgress()`.
- **[NEW]** `RichTextSanitiser` (`app/Support/RichTextSanitiser.php`) — HTML allowlist sanitisation. `Note` model's `setBodyAttribute()` auto-sanitises. Optional `body_raw` + `body_sanitised` when `config('saas.rich_text_audit')` is true. **[NEW]** `SafeHtml` validation rule in `app/Rules/SafeHtml.php`.

### Jobs

- `ProcessImportJob` — `ShouldQueue`, tracks progress, handles errors, updates import record.

### Controllers

- `FileController` — upload, download (signed URL), delete, **[NEW]** request upload URL.
- `NoteController` — CRUD for notes on any noteable.
- `TagController` — CRUD for tags, attach/detach.
- `ImportController` — create import, get status/progress.

### Tests (~42)

- `tests/Feature/Files/FileUploadTest.php` — upload, tenant-scoped, signed download URL, **[NEW]** presigned upload URL, delete, only uploader/admin can delete.
- `tests/Feature/Files/FileScopingTest.php` — tenant isolation.
- `tests/Feature/Notes/NoteCrudTest.php` — CRUD, tenant-scoped, **[NEW]** HTML sanitised, **[NEW]** `SafeHtml` rejects `<script>`, **[NEW]** raw + sanitised stored when config enabled.
- `tests/Feature/Tags/TagCrudTest.php` — CRUD, attach/detach, tenant-scoped.
- `tests/Feature/Imports/ImportTest.php` — create, job dispatched, progress tracked, completed/failed status.
- `tests/Unit/Jobs/ProcessImportJobTest.php` — CSV processing, row counting, errors, progress.
- **[NEW]** `tests/Unit/Support/RichTextSanitiserTest.php` — strips `<script>`, allows `<b>`, `<p>`, handles malicious attributes, re-sanitise idempotent.
- **[SPATIE]** Verify creating/updating/deleting File/Note/Tag/Import auto-creates activity log entries.

---

## Phase 8: Admin Section & Impersonation

**Goal:** Admin backend, impersonation with all guards, admin search, usage stats, audit via Spatie.

### Migrations

1. `admin_notes` table — per §A4.AN.
2. `support_cases` table — per §A4.SC.
3. `webhook_events` table — per §A4A.WE.

### Enums

- `SupportCaseStatus` (Open, Pending, Resolved), `SupportCasePriority` (Low, Medium, High).
- `WebhookEventStatus` (Received, Processed, Failed).
- `AdminNoteTargetType` (User, Tenant).

### Models

- `AdminNote` — **[SPATIE]** `LogsActivity`.
- `SupportCase` — **[SPATIE]** `LogsActivity`.
- `WebhookEvent`.

### Middleware

- `SuperAdminOnly` — **[SPATIE]** checks `$user->hasRole('super-admin')` (global role).
- `StepUpAuth` — requires re-authentication (password confirmation within last N minutes).

### Services

- `ImpersonationService` (`app/Support/Impersonation/ImpersonationService.php`) — `start()`, `stop()`. **[PROMOTED]** Enforces: (a) **cannot impersonate other super admins** — `$targetUser->hasRole('super-admin')` check, (b) **time-limited session** — expires after `config('saas.impersonation_ttl')` minutes, (c) logs start/stop via **[SPATIE]** `activity()->event('impersonation_started')->causedBy($admin)->performedOn($targetUser)->withProperties(['reason' => $reason])->useLog('impersonation')->log(...)`.
- `AdminTenantService` — disable/enable, force logout (revoke all sessions for all tenant members), export data, delete.
- `AdminUserService` — lock/unlock, reset password, revoke sessions.
- **[NEW]** `AdminSearchService` — `search(query)` aggregates results from `tenants`, `users`, `support_cases`.
- **[NEW]** `TenantUsageStatsService` — `getStats(tenant)` returns member count, resource counts, storage used, last activity, subscription status.

### Controllers (Admin)

- `AdminTenantController` — list (filterable), show (detail + **[NEW]** usage stats), members, disable/enable, force logout, export, delete.
- `AdminUserController` — list (filterable), show (detail + memberships + sessions + login history), lock/unlock, reset password, revoke sessions.
- `AdminNoteController` — CRUD notes on users/tenants.
- `SupportCaseController` — CRUD.
- `AdminFeatureFlagController` — manage flags + overrides.
- `ImpersonationController` — start, stop.
- **[NEW]** `AdminSearchController` — `GET /admin/api/v1/search?q=...`.
- **[NEW]** `AdminDashboardController` — `GET /admin/api/v1/dashboard` returns recent admin actions. **[SPATIE]** Queries `Activity` where causer is a super-admin.
- Health check endpoint — DB + queue + broadcast.

### Routes (in `routes/admin.php`)

All under `/admin/api/v1/` prefix, behind `SuperAdminOnly` middleware:
- Tenant management CRUD + actions
- User management CRUD + actions
- Admin notes CRUD
- Support cases CRUD
- Feature flags management
- `POST /admin/api/v1/impersonate/{user}`
- `POST /admin/api/v1/stop-impersonation`
- **[NEW]** `GET /admin/api/v1/search`
- **[NEW]** `GET /admin/api/v1/dashboard`
- **[NEW]** `GET /admin/api/v1/tenants/{tenant}/stats`
- Health check endpoint

### Tests (~58)

- `tests/Feature/Admin/AdminAccessTest.php` — non-super-admin rejected, super admin can access.
- `tests/Feature/Admin/TenantManagementTest.php` — list with filters, view detail, view members, disable/enable, force logout revokes all sessions, export data, delete tenant, **[NEW]** usage stats returned.
- `tests/Feature/Admin/UserManagementTest.php` — list with filters, view detail, view memberships, view sessions + login history, lock/unlock, reset password, revoke sessions.
- `tests/Feature/Admin/AdminNotesTest.php` — CRUD notes on users and tenants.
- `tests/Feature/Admin/SupportCasesTest.php` — CRUD support cases.
- **[NEW]** `tests/Feature/Admin/AdminSearchTest.php` — search returns tenants, users, support cases matching query.
- **[NEW]** `tests/Feature/Admin/AdminDashboardTest.php` — recent admin actions from super admin actors.
- `tests/Feature/Impersonation/ImpersonationTest.php` — start (audit logged with impersonator ID + reason), stop (audit logged), **[PROMOTED]** cannot impersonate other super admins (403), step-up auth required, **[PROMOTED]** time-limited session (expired auto-stops), impersonator ID tracked in all activity during impersonation.
- `tests/Feature/Admin/HealthCheckTest.php` — health returns 200 with DB/queue/broadcast status.
- `tests/Feature/Webhooks/WebhookEventTest.php` — receive, dedupe, process via queue, safe retries, signature verification.

---

## Phase 9: Billing Stub & Rate Limiting

**Goal:** Billing models with activity logging, subscription middleware with explicit read-only mode, rate limiters.

### Migrations

1. `plans` table — id, name, stripe_price_id, features (json), is_active, sort_order, timestamps.
2. `subscriptions` table — id, tenant_id, plan_id, stripe_subscription_id, stripe_customer_id, status (enum), trial_ends_at, current_period_end, grace_period_ends_at, canceled_at, timestamps.

### Enums

- `SubscriptionStatus` (Trialing, Active, PastDue, Canceled, Unpaid).

### Models

- `Plan` — fillable, casts. **[SPATIE]** `LogsActivity`.
- `Subscription` — tenant-scoped, relationships, status check methods. **[SPATIE]** `LogsActivity`.

### Middleware

`EnsureSubscribed` — **[PROMOTED]** explicit read-only mode logic:
- `trialing` / `active` → full access.
- `past_due` → check `grace_period_ends_at`: in grace → full access; expired → **read-only**.
- `canceled` / `unpaid` → **read-only**.
- **Read-only mode** = `GET` requests pass; `POST`/`PUT`/`PATCH`/`DELETE` return 403 `SUBSCRIPTION_READ_ONLY`. Exemptions: billing endpoints, account export, `GET` always allowed.

### Controllers

- `StripeWebhookController` — signature verification, stores in `webhook_events`, dispatches `ProcessWebhookJob`, idempotent.
- **[NEW]** `BillingController` — `GET /api/v1/billing` (current plan + status), `GET /api/v1/billing/plans` (available plans). Stub endpoints.

### Routes

- `POST /api/v1/webhooks/stripe`
- **[NEW]** `GET /api/v1/billing` (auth + tenant required)
- **[NEW]** `GET /api/v1/billing/plans` (auth required)

### Rate Limiters

Registered in `AppServiceProvider` or `bootstrap/app.php`:
- `auth-login`: 10/min per IP + 5/min per email
- `auth-refresh`: 30/min per device_session + 60/min per user
- `auth-logout`: 30/min per user
- `tenant-invites`: 20/hour per tenant + 10/hour per inviter
- `invite-resend`: 5/hour per email
- `admin-impersonate`: 30/hour per super admin
- Keys: user_id, tenant_id, device_session_id, fallback IP

### Tests (~38)

- `tests/Feature/Billing/SubscriptionStateTest.php` — trialing full access, active full access, **[PROMOTED]** past_due in grace → full access, past_due grace expired → read-only (GET 200, POST/PUT/DELETE 403 `SUBSCRIPTION_READ_ONLY`), canceled → read-only, unpaid → read-only, **[PROMOTED]** billing endpoints accessible in read-only, account export accessible in read-only.
- `tests/Feature/Billing/StripeWebhookTest.php` — valid signature, invalid rejected, duplicate deduped, processed via queue, retry idempotent.
- **[NEW]** `tests/Feature/Billing/BillingApiTest.php` — `GET /billing` returns plan + status, `GET /billing/plans` returns available plans.
- `tests/Feature/RateLimiting/AuthRateLimitTest.php` — login per IP + per email, refresh per session + per user, logout per user.
- `tests/Feature/RateLimiting/InviteRateLimitTest.php` — invite per tenant + per inviter, resend per email.
- `tests/Feature/RateLimiting/AdminRateLimitTest.php` — impersonation rate limited.
- **[SPATIE]** Verify subscription status changes auto-logged.

---

## Phase 10: CRUD Generator, Customers Module & Remaining Backend

**Goal:** Generator stubs use Spatie traits. Saved views. Observability with APM hooks. Scheduler.

### Artisan Command

`MakeSaasResource` (`app/Console/Commands/MakeSaasResource.php`) — all flags per §14B.2. Generates all backend + frontend + test files.

### [SPATIE] Generator Stubs

- Model stub includes `use LogsActivity;` trait + `getActivitylogOptions()` + `tapActivity()`.
- Policy stub uses `$user->hasRole([...])` or `$user->hasPermissionTo(...)`.
- Test stub includes assertions that CRUD operations create activity log entries.

### Stubs Directory

`stubs/saas-resource/` — migration, model, factory, policy, controller, form-requests, service, vue-index/show/create/edit, vue-form/table, api-client, pest-test.

### Run Generator

`php artisan make:saas-resource Customer --fields="name:string, email:string?, phone:string?, status:enum(active|inactive)" --soft-deletes --notes --tags --files`

### List Endpoint Conventions (§B3)

All index endpoints accept `page`, `per_page`, `sort`, `search`, `filters[...]`. Implemented in `app/Support/Query/` as `Filterable`, `Sortable`, `Searchable`.

### [NEW] Saved Views (§5.2, §C2)

- Migration: `saved_views` table — `id`, `tenant_id`, `user_id`, `resource_type`, `name`, `config` (json), `is_default`, `created_at`, `updated_at`. Unique index on (`tenant_id`, `user_id`, `resource_type`, `name`).
- `SavedView` model — tenant-scoped.
- `SavedViewController` — CRUD + set default.
- Routes: `GET/POST /api/v1/saved-views`, `PUT/DELETE /api/v1/saved-views/{view}`, `POST /api/v1/saved-views/{view}/default`.

### Observability

- `CorrelationId` middleware — generates UUID request ID, adds to logs + responses. Propagated to queued jobs.
- Structured JSON logging in `config/logging.php`.
- Central exception handler in `bootstrap/app.php`.
- Slow query logging toggle.
- **[NEW]** APM hooks (§9.4) — In `config/saas.php`, add `apm` section with toggles for `sentry_dsn`, `posthog_key`, `otel_endpoint` (all null by default). Conditional registration in `AppServiceProvider::boot()`. Stub classes in `app/Support/Apm/`.

### Scheduler (in `routes/console.php`)

- Prune expired invitations, old activity logs, revoked device sessions.
- **[NEW]** Prune expired magic login tokens, old saved views for deleted users.

### Seeder

Full setup — Super Admin, demo tenant with Owner + Member + Read-only users, Customers, Tags, Notes, Files (stubs), feature flags, **[NEW]** demo saved views.

### Tests (~32)

- `tests/Feature/Generator/MakeSaasResourceTest.php` — command creates all expected files, respects flags.
- `tests/Feature/Customers/CustomerCrudTest.php` — auth required, tenant scoping, policy enforcement, validation errors, index sort + search + filters + pagination.
- `tests/Feature/Customers/CustomerPolicyTest.php` — Owner/Admin CRUD, Member read/create, Read-only read only.
- **[NEW]** `tests/Feature/SavedViews/SavedViewCrudTest.php` — create, list, update, delete, set default, tenant-scoped.
- `tests/Feature/Observability/CorrelationIdTest.php` — request ID in response headers + log context.
- `tests/Feature/Scheduler/CleanupTasksTest.php` — expired invitations pruned, old activity logs pruned, **[NEW]** expired magic tokens pruned.
- **[NEW]** `tests/Feature/Observability/ApmConfigTest.php` — APM integrations register when configured, no-ops when null.

---

## Phase 11: Frontend SPA Foundation

**Goal:** Vue 3 + TypeScript app shell with routing, API client (including optimistic updates), layouts, component library with saved views and autosave.

### Core Setup

- `resources/js/app.ts` — create Vue app, mount router, mount Pinia.
- `resources/js/router/index.ts` — Vue Router with history mode, lazy-loaded routes.
- `resources/js/router/routes.app.ts` — app route definitions.
- `resources/js/router/routes.admin.ts` — admin route definitions.

### API Client

- `resources/js/api/client.ts` — Axios with Bearer token injection, `X-Tenant-ID` header, `X-Request-ID` propagation, 401 refresh interceptor.
- `resources/js/api/errors.ts` — unified error parser.
- `resources/js/api/params.ts` — list parameter helpers.
- **[NEW]** `resources/js/api/optimistic.ts` — `useOptimisticMutation()` composable: apply change locally, fire API, rollback on failure.

### Auth Layer

- `resources/js/auth/useAuth.ts` — login, logout, refresh. Access token memory-only. Refresh token with at-rest obfuscation. Reload → refresh → re-auth.
- `resources/js/auth/guards.ts` — route guards. **[NEW]** Expose `can(action, resource)` helper from permissions payload in `/api/v1/me`.

### Tenancy

- `resources/js/tenancy/useTenant.ts` — composable.
- `resources/js/tenancy/tenantStore.ts` — Pinia store + switcher.

### Layouts

- `AppLayout.vue` — sidebar with **collapsible sections**, tenant switcher, command palette (Ctrl+K), toast notifications, route loading indicator, light/dark mode.
- `AdminLayout.vue` — separate layout, **[NEW]** global admin search bar, **[NEW]** recent admin actions panel.
- `AuthLayout.vue` — centered card.

### Component Library (wrapped shadcn-vue per §6.3.P1)

- `ui/Button.vue`, `ui/Modal.vue`, `ui/Drawer.vue`, `ui/Dropdown.vue`, `ui/Toast.vue`, `ui/Tabs.vue`
- `ui/DataTable/` — TanStack Table wrapper with column toggles, **[NEW]** saved views integration, bulk actions, pagination, sorting, search, row action menus, empty states.
- `ui/Form/` — field components, inline errors, dirty state detection, unsaved changes guard, **[NEW]** optional autosave (`useAutosave()` composable).
- `shared/PageHeader.vue`, `shared/Breadcrumbs.vue`, `shared/EmptyState.vue`, `shared/ConfirmDanger.vue`

### Stores

- `stores/notifications.ts` — notification state + actions.
- `stores/ui.ts` — sidebar state, dark mode, command palette.

### Validation Schemas

**[NEW]** Co-locate zod schemas with API types per domain (`resources/js/api/schemas/customer.ts`, etc.).

### Tests

- `tests/Browser/Auth/LoginFlowTest.php` — login, redirect, token in memory.
- `tests/Browser/Auth/RefreshFlowTest.php` — auto-refresh on expiry.
- `tests/Browser/Shell/AppShellTest.php` — sidebar, dark mode, tenant switcher.
- **[NEW]** `tests/Browser/Shell/CommandPaletteTest.php` — Ctrl+K opens, typing filters.

---

## Phase 12: Frontend Feature Pages

**Goal:** All Vue pages including session/token management UI, invite mismatch UX, billing placeholders.

### Auth Pages

- `pages/auth/Login.vue` — **[NEW]** 2FA challenge step (TOTP + recovery code link).
- `pages/auth/ForgotPassword.vue`, `ResetPassword.vue`, `VerifyEmail.vue`.
- **[NEW]** `pages/auth/MagicLink.vue` — request magic link (when feature enabled).
- **[NEW]** `pages/auth/AcceptInvite.vue` — invite details. Email mismatch state shows "Switch Account" / "Log Out and Continue" buttons.

### App Pages

- `pages/app/Dashboard.vue` — overview with stats.
- `pages/app/Settings/` — profile, email change, password change, **[NEW]** `Sessions.vue` (device session management UI — §2.1), **[NEW]** `Tokens.vue` (PAT management UI — §2.2), notification preferences, **[NEW]** `TwoFactor.vue` (enable/disable 2FA, QR code, recovery codes).
- `pages/app/Customers/` — Index (DataTable + saved views), Show (detail + activity timeline + notes/files/tags), Create, Edit (dirty guard + optional autosave).
- `pages/app/Notes/`, `Files/`, `Imports/` — CRUD pages.
- `pages/app/Notifications/` — list + read/unread + dropdown.
- **[NEW]** `pages/app/Billing/` — `Billing.vue` (current plan, status, upgrade CTA), `Plans.vue` (available plans). Read-only mode shows "Upgrade" banner. These are the **Billing UI placeholders** from §12.1.

### Admin Pages

- `pages/admin/Dashboard.vue` — overview + **[NEW]** recent admin actions widget.
- `pages/admin/Tenants/` — list, detail (with **[NEW]** usage stats tab), members, actions.
- `pages/admin/Users/` — list, detail (sessions, login history, memberships), actions, impersonate button.
- `pages/admin/Jobs/` — queue dashboard, failed jobs retry.
- `pages/admin/Audit/` — activity log viewer.
- `pages/admin/Health/` — system health checks.
- **[NEW]** Admin search results page.

### Impersonation UX

- Persistent banner during impersonation, stop button, **[NEW]** remaining time display.

### Authorization in UI

**[NEW]** All mutation buttons check user's role via `can()` helper. Unauthorized actions hidden or disabled with tooltip.

### API Client Wrappers

- `api/auth.ts`, `api/tenants.ts`, `api/customers.ts`, `api/notes.ts`, `api/files.ts`, `api/tags.ts`, `api/imports.ts`, `api/notifications.ts`, **[NEW]** `api/billing.ts`, **[NEW]** `api/sessions.ts`, **[NEW]** `api/tokens.ts`, **[NEW]** `api/saved-views.ts`, `api/admin/*.ts`.

### Tests

- `tests/Browser/Customers/CustomerCrudFlowTest.php` — create, view, edit, delete, validation, dirty guard, **[NEW]** saved view create + apply.
- `tests/Browser/Admin/TenantManagementFlowTest.php` — list, disable/enable, **[NEW]** usage stats.
- `tests/Browser/Admin/ImpersonationFlowTest.php` — impersonate, banner with timer, stop.
- `tests/Browser/Notifications/NotificationFlowTest.php` — dropdown, mark read.
- **[NEW]** `tests/Browser/Settings/SessionManagementTest.php` — list sessions, revoke, revoke all.
- **[NEW]** `tests/Browser/Settings/TokenManagementTest.php` — create, list, revoke.
- **[NEW]** `tests/Browser/Auth/InviteAcceptFlowTest.php` — matching email works, mismatch shows switch/logout options.
- **[NEW]** `tests/Browser/Billing/BillingPlaceholdersTest.php` — current plan, plans list, read-only CTA.
- **[NEW]** `tests/Browser/Settings/TwoFactorFlowTest.php` — enable, QR, confirm, login with TOTP.
- **[NEW]** `tests/Browser/Admin/AdminSearchFlowTest.php` — global search returns grouped results.
- `tests/Browser/SmokeTest.php` — visit `/login`, `/dashboard`, `/admin`; assert no JS errors.

---

## Phase 13: Realtime (Reverb) & Deliverability

**Goal:** WebSocket setup, presence, realtime notifications, email/SMS deliverability tooling.

### Reverb Setup

- Install and configure Reverb.
- `resources/js/realtime/reverb.ts` — Echo client.
- `resources/js/realtime/channels.ts` — channel definitions.

### Realtime Patterns

- Online/offline indicator (presence channel per tenant).
- Job completion → broadcast → toast notification.
- Soft refresh prompts for updated records.
- Presence indicators on edit pages.

### Deliverability

- Per-tenant sending limits (rate + daily caps).
- Suppression list (global + per-tenant).
- Unsubscribe handling (signed links in emails).
- Bounce/complaint tracking.
- Message logs (queryable by Super Admin in admin ops dashboard).

### Tests

- `tests/Feature/Realtime/BroadcastTest.php` — events on correct channels, tenant-scoped enforcement.
- `tests/Feature/Deliverability/SendingLimitsTest.php` — per-tenant rate + daily cap.
- `tests/Feature/Deliverability/SuppressionTest.php` — suppressed email skipped.

---

## Phase 14: DX, CI, Documentation & Final Hardening

**Goal:** One-command setup, CI pipeline with dependency auditing, retention documentation, backup runbook, security verification.

### Steps

1. **One-command setup** — `composer run setup`: copy `.env.example` → `.env`, `php artisan key:generate`, `php artisan migrate --seed`, `npm install`, `npm run build`.
2. **CI pipeline** — GitHub Actions workflow:
   - Install → `vendor/bin/pint --test` → `php artisan test` → `npm run build`.
   - **[NEW]** `composer audit` step — fails on known vulnerabilities.
   - **[NEW]** `npm audit --audit-level=high` step — fails on high/critical JS vulnerabilities.
   - **[NEW]** Dependency freshness check — report (non-blocking) on packages with no updates in >12 months for auth/crypto/sanitisation categories.
   - (Optional) Static analysis step.
3. **[NEW] Abandoned package policy** — `docs/DEPENDENCY_POLICY.md`: packages for auth, crypto, or HTML sanitisation must have had a release within 12 months. CI flags violations.
4. **Data retention** — Configurable per-tenant for activity logs. `artisan saas:prune-activity-logs` command. **[NEW]** Document in `config/saas.php` what's retained after tenant hard-deletion (billing/compliance logs preserved, all else removed).
5. **Backup config** — Document backup strategy (DB + files), restore runbook, RPO/RTO objectives in `docs/ops/`.
6. **Deploy checklist** — Zero-downtime deploy steps per §9.0A in `docs/ops/deploy.md`.
7. **Final security hardening pass:**
   - Verify CSP nonce implementation end-to-end.
   - **[NEW]** Grep for `v-html` usage — must be zero.
   - Verify lockfiles (`composer.lock`, `package-lock.json`) committed.
   - **[NEW]** Verify ESLint disallows `eval`, `new Function`.
   - Verify no `eval`/`Function` constructors in frontend.

### Tests

- `tests/Feature/Setup/SetupCommandTest.php` — setup runs without errors.
- Full test suite green (`php artisan test`).
- `npm run build` succeeds.
- `vendor/bin/pint --test` passes.
- **[NEW]** CI pipeline YAML includes audit steps.

---

## Verification Protocol

**At each phase boundary:**
1. `php artisan test --filter=<PhaseTestFiles>` — all phase tests green.
2. `vendor/bin/pint --dirty` — code style clean.
3. `npm run build` — frontend compiles (from Phase 11+).

**Final verification:**
- `php artisan test` — full suite green (~370+ tests).
- `npm run build` — no TS/Vue errors.
- `vendor/bin/pint --test` — passes.
- Manual smoke: login → 2FA → tenant switch → CRUD with saved view → refresh token → session management → admin impersonation → admin search → billing placeholder → logout.

---

## Key Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Phasing | Single plan, 14 sequenced phases | Full visibility, sequential dependencies |
| Frontend language | TypeScript | Spec folder structure implies `.ts` files |
| Test database | MySQL | Production parity over SQLite speed |
| Tenant identification | Header-only (`X-Tenant-ID`) | SPA model; subdomain unnecessary for template |
| RBAC engine | **spatie/laravel-permission v6** | Battle-tested, teams feature maps to tenants, zero-schema extension to permissions |
| Audit engine | **spatie/laravel-activitylog v4** | Auto before/after snapshots, extensible pipeline, custom Activity model |
| `tenant_user.role` column | Retained alongside Spatie roles | Spec schema compliance + query convenience; synced via service layer |
| Content retention on delete | Configurable (`anonymise` / `retain`) | Policy decision exposed in config |
| Post-deletion retention | Billing/compliance logs preserved | Documented in config |
| RBAC extension path | Documented, zero schema change needed | Spatie's `permissions` + `role_has_permissions` tables exist from Phase 3 |
| ID strategy | Configurable (`bigint` default, `uuid`/`ulid` optional) | One-config switch via trait |

---

## Requirements Coverage

| Spec Section | Phase(s) | Status |
|---|---|---|
| §1 Core Architecture | 1, 3 | 100% |
| §2.1 Auth (login, 2FA, magic link, session UI, login history) | 2, 12 | 100% |
| §2.2 Sanctum (bearer, me, PATs, token UI) | 2, 12 | 100% |
| §2.2.1 Token storage, refresh, device sessions, reuse detection | 2, 11 | 100% |
| §2.3 Security defaults | 1, 2, 5 | 100% |
| §2.3.1 XSS mitigation | 1, 7, 14 | 100% |
| §3.1 Tenant context + security | 3 | 100% |
| §3.2 Invitations + edge cases | 5, 12 | 100% |
| §3.3 Tenant lifecycle | 3, 8 | 100% |
| §3.4 User account lifecycle | 5 | 100% |
| §4 Authorization + RBAC extension | 4, 11 | 100% |
| §5.2 List views (saved views) | 10, 11 | 100% |
| §5.3 Forms (autosave) | 11 | 100% |
| §6 Frontend architecture | 11, 12 | 100% |
| §7 Realtime (Reverb) | 13 | 100% |
| §8 Notifications, activity log, deliverability | 6, 13 | 100% |
| §9 Background jobs, ops, backups, DR | 7, 10, 14 | 100% |
| §10 Admin section | 8, 12 | 100% |
| §11 Impersonation | 8, 12 | 100% |
| §12 Billing (stub) | 9, 12 | 100% |
| §13 Testing strategy (rate limiting, helpers) | 1, 9 | 100% |
| §14 DX & CI | 1, 14 | 100% |
| §14A/B Folder structure & generator | 1, 10 | 100% |
| §15 Baseline modules | 7, 10 | 100% |
| Appendix A (all tables) | 2, 3, 5, 6, 7, 8, 9 | 100% |
| Appendix B (query conventions) | 10 | 100% |
| Appendix C (UI patterns) | 11, 12 | 100% |

**Total: 347/347 requirements covered. 0 missing.**
**Estimated tests: ~370+**
