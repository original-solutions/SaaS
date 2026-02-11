<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Prune expired tenant invitations (daily at 2:00 AM)
Schedule::call(function (): void {
    $days = config('saas.invite_expiry_days', 7);
    \App\Models\TenantInvitation::whereNull('accepted_at')
        ->where('expires_at', '<', now())
        ->delete();
})->dailyAt('02:00')
    ->description('Prune expired tenant invitations');

// Prune old activity logs based on retention config (daily at 3:00 AM)
Schedule::call(function (): void {
    $days = config('saas.activity_log_retention_days', 365);
    \App\Models\Activity::where('created_at', '<', now()->subDays($days))->delete();
})->dailyAt('03:00')
    ->description('Prune old activity log entries');

// Prune revoked device sessions older than 30 days (daily at 3:30 AM)
Schedule::call(function (): void {
    \App\Models\DeviceSession::where('revoked_at', '<', now()->subDays(30))->delete();
})->dailyAt('03:30')
    ->description('Prune revoked device sessions');

// Prune expired magic login tokens (hourly)
Schedule::call(function (): void {
    \App\Models\MagicLoginToken::where('expires_at', '<', now())->delete();
})->hourly()
    ->description('Prune expired magic login tokens');

// Prune expired refresh tokens (daily at 4:00 AM)
Schedule::call(function (): void {
    $ttl = config('saas.refresh_token_ttl', 10080);
    \App\Models\RefreshToken::where('created_at', '<', now()->subMinutes($ttl))
        ->whereNull('revoked_at')
        ->delete();

    \App\Models\RefreshToken::whereNotNull('revoked_at')
        ->where('revoked_at', '<', now()->subDays(7))
        ->delete();
})->dailyAt('04:00')
    ->description('Prune expired and stale refresh tokens');
