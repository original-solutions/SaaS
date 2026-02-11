---
title: Getting Started
description: How to set up and run the SaaS template
---

# Getting Started

This guide walks you through setting up the Laravel SaaS Base Template and using it as the foundation for your own SaaS product.

## Prerequisites

- PHP 8.4+
- MySQL 8.0+
- Node.js 22+
- Composer
- npm
- Laravel Herd (recommended for local dev)

## 1. Clone and Install

```bash
git clone <your-repo-url>
cd saas
composer install
npm ci
```

## 2. Configure Environment

- Copy `.env.example` to `.env` and fill in your environment variables (DB, mail, etc).
- Generate app key:

```bash
php artisan key:generate
```

## 3. Database Setup

- Run migrations and seeders:

```bash
php artisan migrate --seed
```

## 4. Build Frontend

```bash
npm run build
```

## 5. Run the App

- With Laravel Herd: open the `.test` URL in your browser.
- Or:

```bash
php artisan serve
```

## 6. Running Tests

```bash
php artisan test
```

---

Next: [Architecture](./architecture.md)
