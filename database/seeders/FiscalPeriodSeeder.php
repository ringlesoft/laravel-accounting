<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RingleSoft\LaravelAccounting\Models\FiscalYear;
use RingleSoft\LaravelAccounting\Services\PeriodService;

class FiscalPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $periodService = app(PeriodService::class);

        $fiscalYearStart = config('laravel_accounting.default_fiscal_year_start', [
            'month' => 1,
            'day' => 1,
        ]);

        $currentYear = now()->year;
        $startMonth = $fiscalYearStart['month'];
        $startDay = $fiscalYearStart['day'];

        $startDate = now()->setYear($currentYear)->setMonth($startMonth)->setDay($startDay)->startOfDay();

        if ($startDate->isFuture()) {
            $startDate->subYear();
        }

        $endDate = $startDate->copy()->addYear()->subDay();

        $fiscalYear = FiscalYear::firstOrCreate([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ], [
            'name' => $startDate->year . '-' . $endDate->year,
            'is_closed' => false,
        ]);

        $periodService->createPeriodsForYear($fiscalYear);
    }
}
