# Laravel SaaS Base Template Documentation

Welcome to the documentation for the Laravel SaaS Base Template. This guide will help you understand the architecture, features, and how to use this template to build a real-world SaaS application.

## Overview

- **Stack:** Laravel 12, MySQL, Vue 3 (SPA), Tailwind CSS, Sanctum, Reverb, Pest, Prettier, Pint
- **Purpose:** A production-grade, opinionated SaaS foundation for scalable products.

## Getting Started

### 1. Installation

Clone the repository and install dependencies:

```bash
git clone <your-repo-url>
cd saas
composer install
npm ci
```

Copy `.env.example` to `.env` and configure your environment variables.

### 2. Setup

```bash
php artisan key:generate
php artisan migrate --seed
npm run build
```

### 3. Running the App

```bash
php artisan serve
# or use Laravel Herd for local development
```

Visit the app at the URL provided by Herd or `php artisan serve`.

## Project Structure

- **Backend:** Laravel app in `app/`, routes in `routes/`, config in `config/`
- **Frontend:** Vue 3 SPA in `resources/js/`
- **Tests:** Pest tests in `tests/`
- **Docs:** VitePress docs in `docs/vitepress/`

## Key Features

- Multi-tenancy with team-based roles
- Secure authentication (email/password, 2FA, magic links)
- Device/session management
- Activity/audit logging
- Realtime updates with Reverb
- Modern SPA frontend (Vue 3 + Tailwind)
- Full TDD with Pest

## Using This Template for Your SaaS

1. **Plan your domain models**: Extend the base models and add your own features.
2. **Leverage built-in multi-tenancy**: Use the provided tenant/user/role system.
3. **Add resources**: Use the resource generator to scaffold CRUD (models, controllers, Vue pages, tests).
4. **Secure your app**: Use Form Requests for validation, policies for authorization, and built-in security headers.
5. **Customize the frontend**: Build on the SPA shell, add pages/components as needed.
6. **Test everything**: Write Pest tests for all features.
7. **Deploy with confidence**: Use the provided deployment and backup runbooks.

## Documentation Structure

- [Introduction](./index.md)
- [Getting Started](./getting-started.md)
- [Architecture](./architecture.md)
- [Multi-Tenancy](./multi-tenancy.md)
- [Authentication](./authentication.md)
- [Frontend Guide](./frontend.md)
- [Testing](./testing.md)
- [Deployment](./deployment.md)
- [Backup & Restore](./backup.md)

---

For more details, see the full [specification](../spec.md) and [implementation plan](../plan.md).
