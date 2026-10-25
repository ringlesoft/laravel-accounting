<?php

namespace RingleSoft\LaravelAccounting\Commands;

use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RingleSoft\LaravelAccounting\Services\CustomPresetService;

class InstallCommand extends Command
{
    public $signature = 'laravel-accounting:install {--uuid : Use UUIDs for primary keys}';

    public $description = 'Install Laravel Accounting by Seeding initial data';

    public function handle(): int
    {
        $this->comment('Installing Laravel Accounting');
        $this->newLine();

        $useUuid = $this->option('uuid') ?: $this->confirm(
            'Do you want to use UUIDs instead of auto-incrementing IDs for primary keys?',
            false
        );

        $this->updateConfig($useUuid);

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'accounting-config',
            '--force' => true,
        ]);

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

    protected function updateConfig(bool $useUuid): void
    {
        $configPath = config_path('laravel_accounting.php');

        if (File::exists($configPath)) {
            $config = File::get($configPath);
            $config = preg_replace(
                "/'use_uuid'\s*=>\s*(true|false)/",
                "'use_uuid' => ".($useUuid ? 'true' : 'false'),
                $config
            );
            File::put($configPath, $config);
            $this->info('Updated use_uuid configuration to: '.($useUuid ? 'true' : 'false'));
        }
    }
}
