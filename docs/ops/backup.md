# Backup & Restore Runbook

## Backup Strategy

### Database Backups

- **Frequency:** Daily full backup, hourly incremental (if using managed DB).
- **Retention:** 30 days for daily, 7 days for hourly.
- **Method:** `mysqldump` for logical backups, binary log replication for point-in-time.

```bash
# Manual full backup
mysqldump -u root -p saas --single-transaction --routines --triggers > backup_$(date +%Y%m%d).sql

# Compressed
mysqldump -u root -p saas --single-transaction | gzip > backup_$(date +%Y%m%d).sql.gz
```

### File Backups

- **Storage:** `storage/app/` contains uploaded files.
- **Frequency:** Daily sync to offsite storage (S3, etc.).
- **Method:** `rsync` or cloud provider SDK.

```bash
# Sync to S3
aws s3 sync storage/app/ s3://your-bucket/backups/storage/
```

## Recovery Objectives

| Metric                         | Target     |
| ------------------------------ | ---------- |
| RPO (Recovery Point Objective) | 1 hour     |
| RTO (Recovery Time Objective)  | 30 minutes |

## Restore Procedure

### Database Restore

```bash
# Restore from backup
mysql -u root -p saas < backup_20240101.sql

# Or from compressed
gunzip < backup_20240101.sql.gz | mysql -u root -p saas

# Run any pending migrations
php artisan migrate --force
```

### File Restore

```bash
aws s3 sync s3://your-bucket/backups/storage/ storage/app/
```

### Full Application Restore

1. Clone the repository.
2. Run `composer run setup`.
3. Restore database from latest backup.
4. Restore files from latest backup.
5. Verify with `php artisan test`.

## Data Retention After Tenant Deletion

As configured in `config/saas.php` (`content_retention_on_delete: anonymise`):

- **Preserved:** Billing logs, compliance/audit logs, aggregated analytics.
- **Anonymised:** User personal data, customer data, notes, files.
- **Deleted:** Sessions, tokens, magic links, device sessions.

## Testing Backups

Monthly backup restore test:

1. Restore latest backup to a test environment.
2. Run `php artisan test` against restored data.
3. Verify key data integrity (user count, tenant count, customer counts).
4. Document results and any issues.
