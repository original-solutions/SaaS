<?php

it('app key is set', function (): void {
    expect(config('app.key'))->not->toBeNull()->not->toBeEmpty();
});

it('database connection works', function (): void {
    expect(fn () => \Illuminate\Support\Facades\DB::connection()->getPdo())
        ->not->toThrow(\Exception::class);
});

it('required tables exist', function (): void {
    $tables = [
        'users',
        'tenants',
        'tenant_user',
        'customers',
        'tags',
        'notes',
        'files',
        'imports',
        'subscriptions',
        'plans',
        'tenant_invitations',
        'device_sessions',
        'refresh_tokens',
        'login_events',
        'email_suppressions',
        'message_logs',
        'sending_limits',
    ];

    foreach ($tables as $table) {
        expect(\Illuminate\Support\Facades\Schema::hasTable($table))
            ->toBeTrue("Table '{$table}' should exist");
    }
});

it('lockfiles exist', function (): void {
    expect(file_exists(base_path('composer.lock')))->toBeTrue();
    expect(file_exists(base_path('package-lock.json')))->toBeTrue();
});

it('frontend build output exists', function (): void {
    expect(file_exists(public_path('build/manifest.json')))->toBeTrue();
});

it('ci workflow exists', function (): void {
    expect(file_exists(base_path('.github/workflows/ci.yml')))->toBeTrue();
});

it('no v-html usage in frontend', function (): void {
    $jsDir = resource_path('js');
    $output = [];
    exec("grep -r 'v-html' {$jsDir} --include='*.vue' --include='*.ts' 2>/dev/null", $output);

    expect($output)->toBeEmpty('No v-html usage allowed in frontend code');
});

it('no eval or new Function in frontend', function (): void {
    $jsDir = resource_path('js');
    $output = [];
    exec("grep -rE '\\beval\\(|new\\s+Function\\(' {$jsDir} --include='*.vue' --include='*.ts' 2>/dev/null", $output);

    expect($output)->toBeEmpty('No eval or new Function allowed in frontend code');
});
