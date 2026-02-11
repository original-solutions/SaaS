---
title: Frontend Guide
description: Building the SPA with Vue 3, Vite, and Tailwind
---

# Frontend Guide

## Stack

- Vue 3 (TypeScript)
- Vite
- Tailwind CSS
- shadcn-vue, radix-vue (UI primitives)
- vee-validate + zod (forms)
- Pinia (state)
- TanStack Table (advanced tables)

## Structure

- SPA entry: `resources/views/app.blade.php`
- Main app: `resources/js/app.ts`
- Router: `resources/js/router/`
- Components: `resources/js/components/`
- Pages: `resources/js/pages/`
- Stores: `resources/js/stores/`

## Best Practices

- Store access tokens in memory only
- Use centralized API client for all requests
- Use form validation schemas shared with backend rules
- Wrap shadcn components in local abstractions
- Use TanStack Table for complex tables
- Write feature/browser tests for all UI flows

## Customization

- Add new pages/components as needed
- Use the resource generator for CRUD scaffolds
- Style with Tailwind and shadcn-vue

---

Next: [Testing](./testing.md)
