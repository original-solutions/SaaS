<?php

use Illuminate\Support\Facades\File;

afterEach(function (): void {
    // Cleanup generated files
    $files = [
        app_path('Models/Widget.php'),
        app_path('Policies/WidgetPolicy.php'),
        app_path('Http/Controllers/Api/V1/WidgetController.php'),
        app_path('Http/Requests/Api/V1/StoreWidgetRequest.php'),
        app_path('Http/Requests/Api/V1/UpdateWidgetRequest.php'),
        app_path('Services/WidgetService.php'),
        database_path('factories/WidgetFactory.php'),
        base_path('tests/Feature/Widgets/WidgetCrudTest.php'),
    ];

    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    // Cleanup migration
    $migrations = glob(database_path('migrations/*_create_widgets_table.php'));
    foreach ($migrations as $migration) {
        File::delete($migration);
    }

    // Cleanup test directory
    $testDir = base_path('tests/Feature/Widgets');
    if (File::isDirectory($testDir)) {
        File::deleteDirectory($testDir);
    }
});

it('generates all resource files', function (): void {
    $this->artisan('make:saas-resource', [
        'name' => 'Widget',
        '--fields' => 'title:string, description:text?',
    ])->assertSuccessful();

    expect(File::exists(app_path('Models/Widget.php')))->toBeTrue();
    expect(File::exists(app_path('Policies/WidgetPolicy.php')))->toBeTrue();
    expect(File::exists(app_path('Http/Controllers/Api/V1/WidgetController.php')))->toBeTrue();
    expect(File::exists(app_path('Http/Requests/Api/V1/StoreWidgetRequest.php')))->toBeTrue();
    expect(File::exists(app_path('Http/Requests/Api/V1/UpdateWidgetRequest.php')))->toBeTrue();
    expect(File::exists(app_path('Services/WidgetService.php')))->toBeTrue();
    expect(File::exists(database_path('factories/WidgetFactory.php')))->toBeTrue();
    expect(File::exists(base_path('tests/Feature/Widgets/WidgetCrudTest.php')))->toBeTrue();

    $migrations = glob(database_path('migrations/*_create_widgets_table.php'));
    expect($migrations)->toHaveCount(1);
});

it('includes soft deletes when flag is set', function (): void {
    $this->artisan('make:saas-resource', [
        'name' => 'Widget',
        '--fields' => 'title:string',
        '--soft-deletes' => true,
    ])->assertSuccessful();

    $model = File::get(app_path('Models/Widget.php'));
    expect($model)->toContain('SoftDeletes');
});

it('includes trait flags when set', function (): void {
    $this->artisan('make:saas-resource', [
        'name' => 'Widget',
        '--fields' => 'title:string',
        '--notes' => true,
        '--tags' => true,
        '--files' => true,
    ])->assertSuccessful();

    $model = File::get(app_path('Models/Widget.php'));
    expect($model)->toContain('HasNotes');
    expect($model)->toContain('HasTags');
    expect($model)->toContain('HasFiles');
});

it('generates correct store validation rules', function (): void {
    $this->artisan('make:saas-resource', [
        'name' => 'Widget',
        '--fields' => 'title:string, status:enum(active|inactive)',
    ])->assertSuccessful();

    $request = File::get(app_path('Http/Requests/Api/V1/StoreWidgetRequest.php'));
    expect($request)->toContain("'title'");
    expect($request)->toContain("'required'");
    expect($request)->toContain("'in:active,inactive'");
});
