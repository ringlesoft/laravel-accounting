<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RingleSoft\LaravelAccounting\Services\CustomPresetService;

class CustomPresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the accounts described by #[PresetAccount] on the cases of the
     * enums listed in `laravel_accounting.custom_presets`. Run it after
     * AccountTypeSeeder and ChartOfAccountsSeeder, since custom accounts may
     * sit under built-in parents. Existing accounts are left untouched.
     */
    public function run(): void
    {
        app(CustomPresetService::class)->seed();
    }
}
