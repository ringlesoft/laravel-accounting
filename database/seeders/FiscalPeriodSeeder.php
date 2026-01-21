<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RingleSoft\LaravelAccounting\Services\PeriodService;

class FiscalPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $periodService = app(PeriodService::class);

        $periodService->ensurePeriodsExist(yearsAhead: 0);
    }
}
