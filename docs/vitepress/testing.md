---
title: Testing
description: Pest, TDD, and browser testing
---

# Testing

## Pest

- All tests use Pest (feature, unit, browser)
- Test helpers for acting as different roles/tenants
- Factories for all models

## TDD Workflow

1. Write Pest tests for new features
2. Implement code to make tests pass
3. Run `vendor/bin/pint --dirty` to fix style
4. Re-run tests to verify

## Browser Testing

- Use Pest browser tests for UI flows
- Can test on multiple browsers/devices
- Use `visit()` for real browser automation

## Coverage

- Run with `php artisan test --coverage`
- Aim for high coverage, especially on core flows

---

Next: [Deployment](./deployment.md)
