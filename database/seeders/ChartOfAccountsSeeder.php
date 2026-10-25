<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RingleSoft\LaravelAccounting\Services\ChartOfAccountsService;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the chart of accounts based on IFRS/GAAP standards, with
     * hierarchical structure and globally accepted numbering:
     * - 10000-19999: Assets
     * - 20000-29999: Liabilities
     * - 30000-39999: Equity
     * - 40000-49999: Revenue/Income
     * - 50000-99999: Expenses
     *
     * The accounts are defined by #[PresetAccount] attributes on
     * Enums\AccountPreset, and seeded together with the custom presets
     * registered in `laravel_accounting.custom_presets`. Run AccountTypeSeeder
     * first. Existing accounts are left untouched, so this is safe to re-run.
     */
    public function run(string $industry = 'full'): void
    {
        app(ChartOfAccountsService::class)->seed($industry);
    }
}
