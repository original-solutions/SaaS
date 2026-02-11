# Dependency Policy

## Overview

This document outlines the dependency management policy for the SaaS application. All dependencies must be actively maintained and regularly audited for security vulnerabilities.

## Security-Critical Packages

Packages related to **authentication**, **cryptography**, or **HTML sanitization** must have had a release within the last 12 months. CI flags violations as warnings.

### Current security-critical dependencies

| Package                     | Category      | Purpose                                       |
| --------------------------- | ------------- | --------------------------------------------- |
| `laravel/framework`         | Auth / Crypto | Core framework with auth, encryption, hashing |
| `laravel/sanctum`           | Auth          | API token authentication                      |
| `spatie/laravel-permission` | Auth          | Role-based access control                     |
| `laravel/reverb`            | Auth          | WebSocket authentication                      |

## Auditing

### Automated (CI)

- `composer audit` — runs on every push/PR, **fails** on known vulnerabilities.
- `npm audit --audit-level=high` — runs on every push/PR, **fails** on high/critical JS vulnerabilities.
- Dependency freshness check — reports outdated packages (non-blocking).

### Manual (quarterly)

1. Run `composer outdated --direct` and `npm outdated`.
2. Review any security-critical packages that haven't been released in 12+ months.
3. Evaluate alternatives if a security-critical package appears abandoned.

## Lockfile Policy

- `composer.lock` and `package-lock.json` **must** be committed to version control.
- Never run `composer update` or `npm update` without reviewing changes.

## Adding New Dependencies

Before adding a new dependency:

1. Check the package's release history — last release should be within 12 months for security-critical categories.
2. Review the package's issue tracker and maintenance status.
3. Prefer packages with broad community adoption.
4. For security-critical packages, verify there are no known unpatched vulnerabilities.
