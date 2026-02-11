<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\FeatureFlag;
use App\Models\Note;
use App\Models\Plan;
use App\Models\SavedView;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // Super Admin
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@saas.test',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Demo Tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'status' => 'active',
        ]);

        // Attach super admin to the demo tenant as owner
        $tenant->users()->attach($superAdmin, ['role' => 'owner']);
        setPermissionsTeamId($tenant->id);
        $superAdmin->assignRole('owner');

        // Owner
        $owner = User::factory()->create([
            'name' => 'Jane Owner',
            'email' => 'owner@demo.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $tenant->users()->attach($owner, ['role' => 'owner']);
        setPermissionsTeamId($tenant->id);
        $owner->assignRole('owner');

        // Member
        $member = User::factory()->create([
            'name' => 'Bob Member',
            'email' => 'member@demo.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $tenant->users()->attach($member, ['role' => 'member']);
        $member->assignRole('member');

        // Read-only
        $readonly = User::factory()->create([
            'name' => 'Carol Viewer',
            'email' => 'viewer@demo.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $tenant->users()->attach($readonly, ['role' => 'readonly']);
        $readonly->assignRole('readonly');

        // Plans
        Plan::factory()->create(['name' => 'Starter', 'stripe_price_id' => 'price_starter', 'sort_order' => 1, 'is_active' => true, 'features' => ['max_users' => 5, 'max_customers' => 100]]);
        Plan::factory()->create(['name' => 'Professional', 'stripe_price_id' => 'price_pro', 'sort_order' => 2, 'is_active' => true, 'features' => ['max_users' => 25, 'max_customers' => 1000]]);
        Plan::factory()->create(['name' => 'Enterprise', 'stripe_price_id' => 'price_enterprise', 'sort_order' => 3, 'is_active' => true, 'features' => ['max_users' => -1, 'max_customers' => -1]]);

        // Customers
        $customers = Customer::factory()->count(15)->create(['tenant_id' => $tenant->id]);

        // Tags
        $tags = collect(['VIP', 'Enterprise', 'Churned', 'Trial', 'Priority'])->map(function (string $name) use ($tenant) {
            return Tag::factory()->create(['tenant_id' => $tenant->id, 'name' => $name]);
        });

        // Attach tags to some customers
        $customers->take(5)->each(function (Customer $customer) use ($tags, $tenant): void {
            $pivotData = $tags->random(2)->pluck('id')->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenant->id]])->toArray();
            $customer->tags()->attach($pivotData);
        });

        // Notes on customers
        $customers->take(5)->each(function (Customer $customer) use ($owner): void {
            Note::factory()->create([
                'tenant_id' => $customer->tenant_id,
                'noteable_type' => Customer::class,
                'noteable_id' => $customer->id,
                'body' => fake()->sentence(),
                'author_user_id' => $owner->id,
            ]);
        });

        // Feature flags
        FeatureFlag::factory()->create(['key' => 'beta_dashboard', 'enabled' => true, 'description' => 'Enable beta dashboard UI']);
        FeatureFlag::factory()->create(['key' => 'ai_insights', 'enabled' => false, 'description' => 'AI-powered insights feature']);
        FeatureFlag::factory()->create(['key' => 'bulk_import_v2', 'enabled' => true, 'description' => 'New bulk import engine']);

        // Saved views
        SavedView::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'resource_type' => 'customers',
            'name' => 'Active Customers',
            'config' => ['filters' => ['status' => 'active'], 'sort' => 'name'],
            'is_default' => true,
        ]);
        SavedView::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'resource_type' => 'customers',
            'name' => 'Recent Customers',
            'config' => ['filters' => [], 'sort' => '-created_at'],
            'is_default' => false,
        ]);

        $this->command->info('Seeded: Super Admin (admin@saas.test), Demo Company (owner@demo.test, member@demo.test, viewer@demo.test). Password: "password"');
    }
}
