<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ID Strategy
    |--------------------------------------------------------------------------
    |
    | The primary key strategy used for models. Supported: "bigint", "uuid", "ulid".
    | Changing this affects all models using the HasConfigurableId trait.
    |
    */

    'id_strategy' => env('SAAS_ID_STRATEGY', 'bigint'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | How tenants are identified in requests. Currently uses header-only
    | strategy (X-Tenant-ID). Switch to "subdomain" or "both" if your
    | deployment requires subdomain-based tenant resolution.
    |
    */

    'tenant_identification' => env('SAAS_TENANT_IDENTIFICATION', 'header'),

    /*
    |--------------------------------------------------------------------------
    | Token TTLs
    |--------------------------------------------------------------------------
    */

    'access_token_ttl' => env('SAAS_ACCESS_TOKEN_TTL', 15), // minutes
    'refresh_token_ttl' => env('SAAS_REFRESH_TOKEN_TTL', 10080), // minutes (7 days)
    'magic_link_ttl' => env('SAAS_MAGIC_LINK_TTL', 15), // minutes
    'impersonation_ttl' => env('SAAS_IMPERSONATION_TTL', 60), // minutes

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'revoke_all_on_reuse' => env('SAAS_REVOKE_ALL_ON_REUSE', true),
    'two_factor_enabled' => env('SAAS_TWO_FACTOR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    */

    'invite_expiry_days' => env('SAAS_INVITE_EXPIRY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days after subscription expiry before read-only mode activates.
    |
    */

    'grace_period_days' => env('SAAS_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Content Retention on User Deletion
    |--------------------------------------------------------------------------
    |
    | What happens to authored content when a user deletes their account.
    | "anonymise" — nullify actor fields on authored content.
    | "retain" — keep user_id intact on authored content.
    |
    */

    'content_retention_on_delete' => env('SAAS_CONTENT_RETENTION', 'anonymise'),

    /*
    |--------------------------------------------------------------------------
    | Rich Text Audit
    |--------------------------------------------------------------------------
    |
    | When true, Note model stores both body_raw and body_sanitised for audit.
    |
    */

    'rich_text_audit' => env('SAAS_RICH_TEXT_AUDIT', false),

    /*
    |--------------------------------------------------------------------------
    | Activity Log Retention
    |--------------------------------------------------------------------------
    |
    | Billing and compliance logs (log_name = "billing" or "compliance") are
    | excluded from cleanup and survive tenant hard-deletion.
    |
    */

    'activity_log_retention_days' => env('SAAS_ACTIVITY_LOG_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | APM Integrations
    |--------------------------------------------------------------------------
    |
    | Toggle APM integrations. Set DSN/key to enable; null to disable.
    |
    */

    'apm' => [
        'sentry_dsn' => env('SENTRY_DSN'),
        'posthog_key' => env('POSTHOG_KEY'),
        'otel_endpoint' => env('OTEL_EXPORTER_ENDPOINT'),
    ],
];
