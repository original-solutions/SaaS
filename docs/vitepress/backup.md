---
title: Backup & Restore
description: Backup, restore, and data retention
---

# Backup & Restore

## Backup Strategy

- **Database:** Daily full, hourly incremental (if managed DB)
- **Files:** Daily sync of `storage/app/` to offsite (S3, etc.)
- **Retention:** 30 days for daily, 7 days for hourly

## Recovery Objectives

- **RPO:** 1 hour
- **RTO:** 30 minutes

## Restore Procedure

- Restore DB from backup, run migrations
- Restore files from backup
- Verify with `php artisan test`

## Data Retention After Tenant Deletion

- **Preserved:** Billing logs, compliance/audit logs, analytics
- **Anonymised:** User/customer data, notes, files
- **Deleted:** Sessions, tokens, device sessions

## Testing Backups

- Monthly restore test in test environment
- Run tests and verify key data

---

For details, see [docs/ops/backup.md](../ops/backup.md)
