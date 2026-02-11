<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin bypasses all gate checks
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Gate definition for checking super admin
        Gate::define('is-super-admin', fn ($user) => $user->isSuperAdmin());

        $this->configureRateLimiters();
    }

    protected function configureRateLimiters(): void
    {
        // Auth login: 10/min per IP + 5/min per email
        RateLimiter::for('auth-login', function (Request $request) {
            $email = $request->input('email', '');

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by($email),
            ];
        });

        // Auth refresh: 30/min per device session + 60/min per user
        RateLimiter::for('auth-refresh', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->input('device_session_id', $request->ip())),
                Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Auth logout: 30/min per user
        RateLimiter::for('auth-logout', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Tenant invitations: 20/hour per tenant + 10/hour per inviter
        RateLimiter::for('tenant-invites', function (Request $request) {
            $tenant = TenantContext::get();

            return [
                Limit::perHour(20)->by('tenant:'.($tenant?->id ?: $request->ip())),
                Limit::perHour(10)->by('inviter:'.($request->user()?->id ?: $request->ip())),
            ];
        });

        // Invite resend: 5/hour per email
        RateLimiter::for('invite-resend', function (Request $request) {
            return Limit::perHour(5)->by($request->input('email', $request->ip()));
        });

        // Admin impersonation: 30/hour per super admin
        RateLimiter::for('admin-impersonate', function (Request $request) {
            return Limit::perHour(30)->by('admin:'.($request->user()?->id ?: $request->ip()));
        });
    }
}
