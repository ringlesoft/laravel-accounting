<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

const UUID_QUESTION = 'Do you want to use UUIDs instead of auto-incrementing IDs for primary keys?';
const MIGRATE_QUESTION = 'Do you want to run migrations now?';
const SEED_QUESTION = 'Do you want to seed default data (account types, Chart of Accounts, Fiscal Periods)?';

beforeEach(function () {
    $this->configPath = config_path('laravel_accounting.php');
    File::delete($this->configPath);
});

afterEach(function () {
    // The installer publishes into testbench's skeleton app; remove exactly
    // the files it published so no other test sees them.
    File::delete($this->configPath);
    foreach (['migrations', 'seeders'] as $dir) {
        foreach (File::files(__DIR__."/../database/{$dir}") as $file) {
            File::delete(database_path("{$dir}/".$file->getFilename()));
        }
    }
});

it('writes the chosen key type into a freshly published config', function () {
    $this->artisan('laravel-accounting:install', ['--uuid' => true])
        ->expectsConfirmation(MIGRATE_QUESTION, 'no')
        ->expectsConfirmation(SEED_QUESTION, 'no')
        ->assertSuccessful();

    expect(File::get($this->configPath))->toContain("'use_uuid' => true")
        ->and(config('laravel_accounting.use_uuid'))->toBeTrue();
});

it('keeps an existing config file instead of overwriting it', function () {
    File::put($this->configPath, "<?php\n\n// my edits\nreturn [\n    'use_uuid' => false,\n];\n");

    $this->artisan('laravel-accounting:install')
        ->expectsConfirmation(UUID_QUESTION, 'yes')
        ->expectsConfirmation(MIGRATE_QUESTION, 'no')
        ->expectsConfirmation(SEED_QUESTION, 'no')
        ->assertSuccessful();

    expect(File::get($this->configPath))
        ->toContain('// my edits')
        ->toContain("'use_uuid' => true");
});

it('migrates with the chosen key type in the same run', function () {
    $this->artisan('laravel-accounting:install', ['--uuid' => true])
        ->expectsConfirmation(MIGRATE_QUESTION, 'yes')
        ->expectsConfirmation(SEED_QUESTION, 'no')
        ->assertSuccessful();

    expect(Schema::getColumnType('la_accounts', 'id'))->not->toBe('integer');
});

it('refuses to change the key type once the tables exist', function () {
    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

    $this->artisan('laravel-accounting:install', ['--uuid' => true])
        ->expectsOutputToContain('cannot be changed by re-running the installer')
        ->expectsConfirmation(MIGRATE_QUESTION, 'no')
        ->expectsConfirmation(SEED_QUESTION, 'no')
        ->assertSuccessful();

    expect(config('laravel_accounting.use_uuid'))->toBeFalse()
        ->and(File::get($this->configPath))->toContain("'use_uuid' => false");
});
