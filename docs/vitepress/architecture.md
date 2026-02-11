---
title: Architecture
description: Core architecture and design principles
---

# Architecture

## Overview

- **Backend:** Laravel 12 API, MySQL, Sanctum, Reverb
- **Frontend:** Vue 3 SPA, Tailwind CSS, Vite
- **Testing:** Pest (feature, unit, browser)
- **Ops:** Automated backups, zero-downtime deploys

## Key Concepts

### API Versioning

- All endpoints are under `/api/v1/*`.

### Multi-Tenancy

- Users can belong to multiple tenants (organisations/workspaces).
- Tenant context is sent via `X-Tenant-ID` header.
- Roles: Owner, Admin, Member, Read-only (per tenant); Super Admin (global).

### Authentication & Security

- Email/password login, optional 2FA, magic links
- Device/session management
- Access tokens in memory only (never localStorage)
- Refresh token rotation, reuse detection, audit logging
- Strict security headers (CSP, HSTS, etc.)

### Activity Logging

- All model changes are audited (Spatie Activitylog)
- Custom Activity model with tenant context

### Frontend

- SPA shell with Vue Router, Pinia, shadcn-vue UI
- TypeScript-first
- Centralized API client, error handling, and state

---

Next: [Multi-Tenancy](./multi-tenancy.md)
