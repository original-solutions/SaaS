---
title: Deployment
description: Zero-downtime deploys and health checks
---

# Deployment

## Zero-Downtime Deploy

1. (Optional) Put app in maintenance mode:
    ```bash
    php artisan down --retry=60 --secret="deploy-bypass-token"
    ```
2. Pull latest code:
    ```bash
    git pull origin main
    ```
3. Install dependencies:
    ```bash
    composer install --no-dev --optimize-autoloader --no-interaction
    npm ci
    ```
4. Build frontend assets:
    ```bash
    npm run build
    ```
5. Run migrations:
    ```bash
    php artisan migrate --force
    ```
6. Clear and warm caches:
    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    ```
7. Restart queue workers:
    ```bash
    php artisan queue:restart
    ```
8. Restart Reverb (if running):
    ```bash
    php artisan reverb:restart
    ```
9. Bring app up:
    ```bash
    php artisan up
    ```

## Health Checks

- `/up` endpoint for liveness
- `/admin/health` dashboard

---

Next: [Backup & Restore](./backup.md)
