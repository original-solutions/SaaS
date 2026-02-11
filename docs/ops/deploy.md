# Deployment Guide

## Zero-Downtime Deployment

### Prerequisites

- PHP 8.4+, MySQL 8.0+, Node.js 22+, Redis (optional, for queue/cache)
- Reverb configured for WebSocket support

### Deploy Steps

1. **Put application in maintenance mode** (optional for zero-downtime):
   ```bash
   php artisan down --retry=60 --secret="deploy-bypass-token"
   ```

2. **Pull latest code:**
   ```bash
   git pull origin main
   ```

3. **Install dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   npm ci
   ```

4. **Build frontend assets:**
   ```bash
   npm run build
   ```

5. **Run migrations:**
   ```bash
   php artisan migrate --force
   ```

6. **Clear and warm caches:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```

7. **Restart queue workers:**
   ```bash
   php artisan queue:restart
   ```

8. **Restart Reverb** (if running):
   ```bash
   php artisan reverb:restart
   ```

9. **Bring application up:**
   ```bash
   php artisan up
   ```

### Rollback Procedure

1. Revert to previous release tag or commit.
2. Run `php artisan migrate:rollback --step=N` if needed.
3. Rebuild assets and restart workers.

## Health Checks

- `/up` — Laravel built-in health check endpoint.
- Admin panel health dashboard at `/admin/health`.
