---
title: Multi-Tenancy
description: Tenant model, context, and best practices
---

# Multi-Tenancy

## Tenant Model

- Tenant = Organisation/Workspace
- Users can belong to multiple tenants (via `tenant_user` pivot)
- Roles are scoped per tenant (Owner, Admin, Member, Read-only)

## Tenant Context

- Tenant is resolved via `X-Tenant-ID` header (SPA stores in state)
- Middleware enforces tenant membership and status
- No tenant: only auth and onboarding endpoints allowed

## Best Practices

- Always scope queries by tenant
- Never trust `X-Tenant-ID` blindly—validate membership
- Use policies for all tenant-scoped actions

## Invitations

- Invite by email, signed links
- Accept/decline flow, role assignment
- Handles duplicate, expired, and cross-account invites

## Tenant Lifecycle

- Create, disable, delete (soft/hard), export
- Data retention and anonymisation on delete (see [Backup & Restore](./backup.md))

---

Next: [Authentication](./authentication.md)
