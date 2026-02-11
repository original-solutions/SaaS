<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeSaasResource extends Command
{
    protected $signature = 'make:saas-resource
                            {name : The name of the resource (e.g., Customer)}
                            {--fields= : Comma-separated field definitions (e.g., name:string, email:string?, status:enum(active|inactive))}
                            {--soft-deletes : Include SoftDeletes trait}
                            {--notes : Include HasNotes trait}
                            {--tags : Include HasTags trait}
                            {--files : Include HasFiles trait}';

    protected $description = 'Generate a full SaaS resource (model, migration, factory, policy, controller, form requests, service, tests)';

    public function handle(): int
    {
        $name = $this->argument('name');
        $fields = $this->parseFields($this->option('fields') ?? '');
        $softDeletes = $this->option('soft-deletes');
        $hasNotes = $this->option('notes');
        $hasTags = $this->option('tags');
        $hasFiles = $this->option('files');

        $studly = Str::studly($name);
        $snake = Str::snake($name);
        $plural = Str::pluralStudly($name);
        $pluralSnake = Str::snake($plural);
        $kebab = Str::kebab($plural);

        $this->info("Generating SaaS resource: {$studly}");

        // Migration
        $this->generateMigration($pluralSnake, $fields, $softDeletes);

        // Model
        $this->generateModel($studly, $fields, $softDeletes, $hasNotes, $hasTags, $hasFiles);

        // Factory
        $this->generateFactory($studly, $fields);

        // Policy
        $this->generatePolicy($studly);

        // Controller
        $this->generateController($studly, $pluralSnake, $fields);

        // Form Requests
        $this->generateFormRequests($studly, $fields);

        // Service
        $this->generateService($studly);

        // Routes stub
        $this->info('Routes: Add to routes/api_v1.php under tenant-scoped group:');
        $this->line("  Route::apiResource('{$kebab}', \\App\\Http\\Controllers\\Api\\V1\\{$studly}Controller::class);");

        // Test
        $this->generateTest($studly, $kebab, $fields);

        $this->info("Resource {$studly} generated successfully!");

        return self::SUCCESS;
    }

    /**
     * @return array<array{name: string, type: string, nullable: bool, enum_values: array<string>}>
     */
    protected function parseFields(string $fieldsString): array
    {
        if (empty($fieldsString)) {
            return [];
        }

        $fields = [];
        foreach (explode(',', $fieldsString) as $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }

            [$fieldName, $type] = explode(':', $field, 2);
            $fieldName = trim($fieldName);
            $type = trim($type);
            $nullable = str_ends_with($type, '?');
            $type = rtrim($type, '?');
            $enumValues = [];

            if (preg_match('/^enum\((.+)\)$/', $type, $matches)) {
                $type = 'enum';
                $enumValues = array_map('trim', explode('|', $matches[1]));
            }

            $fields[] = [
                'name' => $fieldName,
                'type' => $type,
                'nullable' => $nullable,
                'enum_values' => $enumValues,
            ];
        }

        return $fields;
    }

    protected function generateMigration(string $tableName, array $fields, bool $softDeletes): void
    {
        $columns = '';
        foreach ($fields as $field) {
            $col = match ($field['type']) {
                'string' => "\$table->string('{$field['name']}')",
                'text' => "\$table->text('{$field['name']}')",
                'integer' => "\$table->integer('{$field['name']}')",
                'boolean' => "\$table->boolean('{$field['name']}')->default(false)",
                'enum' => "\$table->string('{$field['name']}')",
                'decimal' => "\$table->decimal('{$field['name']}', 10, 2)",
                'date' => "\$table->date('{$field['name']}')",
                'datetime' => "\$table->dateTime('{$field['name']}')",
                default => "\$table->string('{$field['name']}')",
            };

            if ($field['nullable']) {
                $col .= '->nullable()';
            }

            $columns .= "            {$col};\n";
        }

        $softDeleteLine = $softDeletes ? "            \$table->softDeletes();\n" : '';

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
{$columns}{$softDeleteLine}            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;

        $timestamp = date('Y_m_d_His');
        $path = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        file_put_contents($path, $content);
        $this->info("Migration created: {$path}");
    }

    protected function generateModel(string $name, array $fields, bool $softDeletes, bool $hasNotes, bool $hasTags, bool $hasFiles): void
    {
        $uses = ['use App\Support\Tenancy\BelongsToTenant;', 'use Illuminate\Database\Eloquent\Factories\HasFactory;', 'use Illuminate\Database\Eloquent\Model;', 'use Spatie\Activitylog\LogOptions;', 'use Spatie\Activitylog\Traits\LogsActivity;'];
        $traits = ['BelongsToTenant', 'HasFactory', 'LogsActivity'];

        if ($softDeletes) {
            $uses[] = 'use Illuminate\Database\Eloquent\SoftDeletes;';
            $traits[] = 'SoftDeletes';
        }
        if ($hasNotes) {
            $uses[] = 'use App\Support\Traits\HasNotes;';
            $traits[] = 'HasNotes';
        }
        if ($hasTags) {
            $uses[] = 'use App\Support\Traits\HasTags;';
            $traits[] = 'HasTags';
        }
        if ($hasFiles) {
            $uses[] = 'use App\Support\Traits\HasFiles;';
            $traits[] = 'HasFiles';
        }

        sort($uses);
        $usesStr = implode("\n", $uses);
        $traitsStr = implode(', ', $traits);

        $fillable = array_merge(['tenant_id'], array_column($fields, 'name'));
        $fillableStr = implode("',\n        '", $fillable);

        $casts = '';
        foreach ($fields as $field) {
            if ($field['type'] === 'boolean') {
                $casts .= "            '{$field['name']}' => 'boolean',\n";
            } elseif ($field['type'] === 'datetime' || $field['type'] === 'date') {
                $casts .= "            '{$field['name']}' => 'datetime',\n";
            }
        }

        $castsMethod = '';
        if ($casts) {
            $castsMethod = <<<PHP

    protected function casts(): array
    {
        return [
{$casts}        ];
    }

PHP;
        }

        $content = <<<PHP
<?php

namespace App\Models;

{$usesStr}

class {$name} extends Model
{
    use {$traitsStr};

    protected \$fillable = [
        '{$fillableStr}',
    ];
{$castsMethod}
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

PHP;

        $path = app_path("Models/{$name}.php");
        file_put_contents($path, $content);
        $this->info("Model created: {$path}");
    }

    protected function generateFactory(string $name, array $fields): void
    {
        $definitions = "'tenant_id' => \\App\\Models\\Tenant::factory(),\n";
        foreach ($fields as $field) {
            $definition = match ($field['type']) {
                'string' => 'fake()->word()',
                'text' => 'fake()->paragraph()',
                'integer' => 'fake()->randomNumber()',
                'boolean' => 'false',
                'enum' => "'".($field['enum_values'][0] ?? 'default')."'",
                'decimal' => 'fake()->randomFloat(2, 0, 1000)',
                'date', 'datetime' => 'now()',
                default => 'fake()->word()',
            };

            $definitions .= "            '{$field['name']}' => {$definition},\n";
        }

        $content = <<<PHP
<?php

namespace Database\Factories;

use App\Models\\{$name};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<{$name}>
 */
class {$name}Factory extends Factory
{
    protected \$model = {$name}::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            {$definitions}        ];
    }
}

PHP;

        $path = database_path("factories/{$name}Factory.php");
        file_put_contents($path, $content);
        $this->info("Factory created: {$path}");
    }

    protected function generatePolicy(string $name): void
    {
        $content = <<<PHP
<?php

namespace App\Policies;

use App\Models\\{$name};
use App\Models\User;

class {$name}Policy
{
    public function viewAny(User \$user): bool
    {
        return true;
    }

    public function view(User \$user, {$name} \$model): bool
    {
        return true;
    }

    public function create(User \$user): bool
    {
        return \$user->hasRole(['owner', 'admin', 'member']);
    }

    public function update(User \$user, {$name} \$model): bool
    {
        return \$user->hasRole(['owner', 'admin']);
    }

    public function delete(User \$user, {$name} \$model): bool
    {
        return \$user->hasRole(['owner', 'admin']);
    }
}

PHP;

        $path = app_path("Policies/{$name}Policy.php");
        file_put_contents($path, $content);
        $this->info("Policy created: {$path}");
    }

    protected function generateController(string $name, string $pluralSnake, array $fields): void
    {
        $variable = Str::camel($name);

        $content = <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\\Store{$name}Request;
use App\Http\Requests\Api\V1\\Update{$name}Request;
use App\Models\\{$name};
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class {$name}Controller extends Controller
{
    public function index(Request \$request): JsonResponse
    {
        \$query = {$name}::query()
            ->applySearch(\$request, ['name'])
            ->applySort(\$request, ['name', 'created_at'])
            ->applyFilters(\$request, ['status']);

        return response()->json(\$query->paginate(\$request->integer('per_page', 15)));
    }

    public function store(Store{$name}Request \$request): JsonResponse
    {
        \$model = {$name}::create([
            'tenant_id' => app(TenantContext::class)->id(),
            ...\$request->validated(),
        ]);

        return response()->json(['data' => \$model], 201);
    }

    public function show({$name} \${$variable}): JsonResponse
    {
        return response()->json(['data' => \${$variable}]);
    }

    public function update(Update{$name}Request \$request, {$name} \${$variable}): JsonResponse
    {
        \${$variable}->update(\$request->validated());

        return response()->json(['data' => \${$variable}->fresh()]);
    }

    public function destroy({$name} \${$variable}): JsonResponse
    {
        \${$variable}->delete();

        return response()->json(null, 204);
    }
}

PHP;

        $path = app_path("Http/Controllers/Api/V1/{$name}Controller.php");
        file_put_contents($path, $content);
        $this->info("Controller created: {$path}");
    }

    protected function generateFormRequests(string $name, array $fields): void
    {
        $storeRules = '';
        $updateRules = '';

        foreach ($fields as $field) {
            $rule = match ($field['type']) {
                'string' => "'string', 'max:255'",
                'text' => "'string'",
                'integer' => "'integer'",
                'boolean' => "'boolean'",
                'enum' => "'string', 'in:".implode(',', $field['enum_values'])."'",
                'decimal' => "'numeric'",
                'date' => "'date'",
                'datetime' => "'date'",
                default => "'string'",
            };

            $required = $field['nullable'] ? "'nullable'" : "'required'";

            $storeRules .= "            '{$field['name']}' => [{$required}, {$rule}],\n";
            $updateRules .= "            '{$field['name']}' => ['sometimes', {$rule}],\n";
        }

        // Store request
        file_put_contents(
            app_path("Http/Requests/Api/V1/Store{$name}Request.php"),
            <<<PHP
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class Store{$name}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
{$storeRules}        ];
    }
}

PHP
        );

        // Update request
        file_put_contents(
            app_path("Http/Requests/Api/V1/Update{$name}Request.php"),
            <<<PHP
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class Update{$name}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
{$updateRules}        ];
    }
}

PHP
        );

        $this->info('Form requests created');
    }

    protected function generateService(string $name): void
    {
        $content = <<<PHP
<?php

namespace App\Services;

use App\Models\\{$name};

class {$name}Service
{
    /**
     * Additional business logic for {$name} operations.
     */
}

PHP;

        $path = app_path("Services/{$name}Service.php");
        file_put_contents($path, $content);
        $this->info("Service created: {$path}");
    }

    protected function generateTest(string $name, string $kebab, array $fields): void
    {
        $factoryState = '';
        foreach ($fields as $field) {
            if (! $field['nullable']) {
                $value = match ($field['type']) {
                    'string' => "'Test Value'",
                    'text' => "'Test text content'",
                    'integer' => '1',
                    'boolean' => 'true',
                    'enum' => "'".($field['enum_values'][0] ?? 'default')."'",
                    default => "'test'",
                };
                $factoryState .= "    '{$field['name']}' => {$value},\n";
            }
        }

        $content = <<<PHP
<?php

use App\Models\\{$name};
use App\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    \$this->user = User::factory()->create();
    \$this->tenant = Tenant::factory()->create();
    \$this->tenant->users()->attach(\$this->user, ['role' => 'owner']);
    \$this->actingAs(\$this->user, 'sanctum');
});

it('lists {$kebab}', function (): void {
    {$name}::factory()->count(3)->create(['tenant_id' => \$this->tenant->id]);

    \$this->withHeader('X-Tenant-ID', (string) \$this->tenant->id)
        ->getJson('/api/v1/{$kebab}')
        ->assertSuccessful();
});

it('creates a {$kebab} record', function (): void {
    \$this->withHeader('X-Tenant-ID', (string) \$this->tenant->id)
        ->postJson('/api/v1/{$kebab}', [
{$factoryState}        ])
        ->assertCreated();
});

it('shows a {$kebab} record', function (): void {
    \$model = {$name}::factory()->create(['tenant_id' => \$this->tenant->id]);

    \$this->withHeader('X-Tenant-ID', (string) \$this->tenant->id)
        ->getJson("/api/v1/{$kebab}/{\$model->id}")
        ->assertSuccessful();
});

it('deletes a {$kebab} record', function (): void {
    \$model = {$name}::factory()->create(['tenant_id' => \$this->tenant->id]);

    \$this->withHeader('X-Tenant-ID', (string) \$this->tenant->id)
        ->deleteJson("/api/v1/{$kebab}/{\$model->id}")
        ->assertNoContent();
});

it('logs activity on create', function (): void {
    \$this->withHeader('X-Tenant-ID', (string) \$this->tenant->id)
        ->postJson('/api/v1/{$kebab}', [
{$factoryState}        ])
        ->assertCreated();

    \$this->assertDatabaseHas('activity_log', [
        'subject_type' => {$name}::class,
        'event' => 'created',
    ]);
});

PHP;

        $pluralStudly = Str::plural($name);
        $dir = base_path("tests/Feature/{$pluralStudly}");
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "{$dir}/{$name}CrudTest.php";
        file_put_contents($path, $content);
        $this->info("Test created: {$path}");
    }
}
