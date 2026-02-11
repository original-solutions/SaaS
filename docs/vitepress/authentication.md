---
title: Authentication
description: Auth, 2FA, device sessions, and security
---

# Authentication & Security

## Auth Flows

- Email/password login
- Email verification
- Password reset (revokes all sessions/tokens)
- Optional: Magic link login, 2FA (TOTP + recovery codes)

## Device & Session Management

- Each login creates a device session
- Users can view/revoke sessions
- Refresh tokens are per-device, rotated on use
- Reuse detection triggers audit + admin alert

## Security Defaults

- Form Request validation everywhere
- Mass assignment protection
- Rate limiting on sensitive endpoints
- Strict CSP, HSTS, X-Content-Type-Options, Referrer-Policy
- No access tokens in localStorage/sessionStorage

## Personal Access Tokens

- For integrations (optional)
- CRUD via API and UI

---

Next: [Frontend Guide](./frontend.md)
