<?php

it('has apm configuration in saas config', function (): void {
    expect(config('saas.apm'))->toBeArray();
    expect(config('saas.apm'))->toHaveKeys(['sentry_dsn', 'posthog_key', 'otel_endpoint']);
});

it('defaults apm values to null', function (): void {
    expect(config('saas.apm.sentry_dsn'))->toBeNull();
    expect(config('saas.apm.posthog_key'))->toBeNull();
    expect(config('saas.apm.otel_endpoint'))->toBeNull();
});
