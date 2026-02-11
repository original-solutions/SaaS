---
title: Real-Life SaaS Guide
description: Step-by-step guide to building a SaaS app with this template
---

# Real-Life SaaS Guide

This guide walks you through using the Laravel SaaS Base Template to build a real SaaS product.

## 1. Plan Your Domain

- Identify your core models (e.g., Project, Subscription, Invoice)
- Decide on tenant-scoped vs. global resources

## 2. Scaffold Resources

- Use the resource generator to create models, migrations, controllers, policies, Vue pages, and tests
- Example:
  ```bash
  php artisan make:resource Project
  ```
- Customize generated files as needed

## 3. Implement Business Logic

- Add services, jobs, and listeners in `app/`
- Use Form Requests for validation
- Write policies for all actions

## 4. Build the Frontend

- Add pages/components in `resources/js/pages/` and `components/`
- Use Pinia for state, shadcn-vue for UI
- Connect to API via centralized client

## 5. Secure Your App

- Use built-in authentication, 2FA, device/session management
- Apply policies everywhere
- Validate all input

## 6. Test Everything

- Write Pest tests for all features
- Use browser tests for UI flows

## 7. Deploy and Operate

- Follow the deployment and backup runbooks
- Monitor health endpoints and logs

---

For more, see [spec.md](../spec.md) and [plan.md](../plan.md).
