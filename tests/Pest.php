<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create and act as a super admin user.
 */
function actingAsSuperAdmin(): Tests\TestCase
{
    $user = App\Models\User::factory()->create([
        'is_super_admin' => true,
    ]);

    // Spatie role assignment will be added in Phase 3 after role seeder exists
    return test()->actingAs($user, 'sanctum');
}

/**
 * Create and act as a tenant owner.
 */
function actingAsTenantOwner(?App\Models\User $user = null): Tests\TestCase
{
    $user ??= App\Models\User::factory()->create();

    return test()->actingAs($user, 'sanctum');
}

/**
 * Create and act as a tenant member.
 */
function actingAsMember(?App\Models\User $user = null): Tests\TestCase
{
    $user ??= App\Models\User::factory()->create();

    return test()->actingAs($user, 'sanctum');
}

/**
 * Create and act as a read-only tenant user.
 */
function actingAsReadOnly(?App\Models\User $user = null): Tests\TestCase
{
    $user ??= App\Models\User::factory()->create();

    return test()->actingAs($user, 'sanctum');
}
