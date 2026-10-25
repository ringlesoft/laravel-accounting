<?php

namespace RingleSoft\LaravelAccounting\Commands;

use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RingleSoft\LaravelAccounting\Services\CustomPresetService;

class InstallCommand extends Command
{
    public $signature = 'laravel-accounting:install {--uuid : Use UUIDs for primary keys}';

    public $description = 'Install Laravel Accounting by Seeding initial data';

    public function handle(): int
    {
        $this->comment('Installing Laravel Accounting');
        $this->newLine();

        $useUuid = $this->resolveUseUuid();

        // Publish first, without --force, so an existing config is never
        // overwritten - then record the key type in the published file.
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'accounting-config',
        ]);

        $this->updateConfig($useUuid);

        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'accounting-migrations',
            '--force' => true,
        ]);

        $this->info('Publishing seeders...');
        $this->call('vendor:publish', [
            '--tag' => 'accounting-seeders',
            '--force' => true,
        ]);

        if ($this->confirm('Do you want to run migrations now?', true)) {
            $this->info('Running migrations...');
            $this->call('migrate');
        }

        if ($this->confirm('Do you want to seed default data (account types, Chart of Accounts, Fiscal Periods)?', true)) {
            $this->info('Seeding default data...');
            $this->call('db:seed', [
                '--class' => 'AccountTypeSeeder',
            ]);
            (new ChartOfAccountsSeeder)->run();
            if (config('laravel_accounting.custom_presets', [])) {
                $created = app(CustomPresetService::class)->seed();
                $this->line('  - Custom preset accounts created: '.count($created));
            }
            $this->call('db:seed', [
                '--class' => 'FiscalPeriodSeeder',
            ]);
        }

        $this->newLine();
        $this->info('✓ Laravel Accounting installed successfully!');
        $this->newLine();
        $this->comment('Configuration:');
        $this->line('  - Primary Key Type: '.($useUuid ? 'UUID' : 'Auto-incrementing ID'));
        $this->line('  - Table Prefix: '.config('laravel_accounting.table_prefix', 'la'));
        $this->line('  - Default Currency: '.config('laravel_accounting.default_currency_code', 'USD'));
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Whether to use UUID primary keys: --uuid, else ask (defaulting to the
     * current config). Once the package's tables exist the key type is
     * fixed, so a different answer is ignored with a warning.
     */
    protected function resolveUseUuid(): bool
    {
        $current = (bool) config('laravel_accounting.use_uuid', false);

        $useUuid = $this->option('uuid') ?: $this->confirm(
            'Do you want to use UUIDs instead of auto-incrementing IDs for primary keys?',
            $current
        );

        $accountsTable = config('laravel_accounting.table_prefix', 'la').'_accounts';
        if ($useUuid !== $current && Schema::hasTable($accountsTable)) {
            $this->warn(
                "The {$accountsTable} table already exists with ".($current ? 'UUID' : 'auto-incrementing').
                ' primary keys, so the key type cannot be changed by re-running the installer. Keeping it.'
            );

            return $current;
        }

        return $useUuid;
    }

    /**
     * Write use_uuid to the published config file, and to the running config:
     * migrations run below in this same process read the in-memory value.
     */
    protected function updateConfig(bool $useUuid): void
    {
        config()->set('laravel_accounting.use_uuid', $useUuid);

        $configPath = config_path('laravel_accounting.php');
        $value = $useUuid ? 'true' : 'false';

        if (! File::exists($configPath)) {
            $this->warn("Could not find {$configPath}; set 'use_uuid' => {$value} in your config manually.");

            return;
        }

        $config = File::get($configPath);
        $updated = preg_replace("/'use_uuid'\s*=>\s*(true|false)/", "'use_uuid' => {$value}", $config, 1, $count);

        if (! $count) {
            $this->warn("No 'use_uuid' key found in {$configPath}; add 'use_uuid' => {$value} manually.");

            return;
        }

        if ($updated !== $config) {
            File::put($configPath, $updated);
        }
        $this->info("Set use_uuid to {$value} in {$configPath}");
    }
}
