<?php

namespace RingleSoft\LaravelAccounting\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Exceptions\PeriodClosedException;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\FiscalYear;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use Throwable;

class PeriodService
{
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Open a fiscal period
     * @throws Throwable
     */
    public function openPeriod(FiscalPeriod $period, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($period) {
            if (! $period->is_closed) {
                return true;
            }

            if ($period->is_locked) {
                throw new PeriodClosedException('Cannot reopen a locked period');
            }

            $period->update([
                'is_closed' => false,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            return true;
        });
    }

    /**
     * Close a fiscal period
     * @throws Throwable
     */
    public function closePeriod(FiscalPeriod $period, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($period, $userId) {
            if ($period->is_closed) {
                return true;
            }

            $this->validatePeriodCanBeClosed($period);

            $this->balanceService->updateCachedBalances($period);

            $period->update([
                'is_closed' => true,
                'closed_at' => now(),
                'closed_by_user_id' => $userId,
            ]);

            return true;
        });
    }

    /**
     * Lock a fiscal period
     * @throws Throwable
     */
    public function lockPeriod(FiscalPeriod $period, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($period) {
            if (! $period->is_closed) {
                throw new PeriodClosedException('Cannot lock a period that is not closed');
            }

            if ($period->is_locked) {
                return true;
            }

            $period->update([
                'is_locked' => true,
            ]);

            return true;
        });
    }

    /**
     * Unlock a fiscal period
     */
    public function unlockPeriod(FiscalPeriod $period, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($period) {
            if (! $period->is_locked) {
                return true;
            }

            $period->update([
                'is_locked' => false,
            ]);

            return true;
        });
    }

    /**
     * Roll forward balances from one period to another
     * @throws Throwable
     */
    public function rollForwardBalances(FiscalPeriod $from, FiscalPeriod $to): void
    {
        DB::transaction(function () use ($from, $to) {
            if (! $from->is_closed) {
                throw new PreviousPeriodNotClosedException('Source period must be closed before rolling forward balances');
            }

            $this->balanceService->updateCachedBalances($from);
            $this->balanceService->updateCachedBalances($to);
        });
    }

    /**
     * Create fiscal periods for a fiscal year
     */
    public function createPeriodsForYear(FiscalYear $year, ?string $frequency = null): array
    {
        if ($frequency === null) {
            $frequency = $this->getFrequencyFromConfig();
        }

        return DB::transaction(function () use ($year, $frequency) {
            $periods = [];
            $startDate = $year->start_date->copy();
            $endDate = $year->end_date;
            $periodsCount = config('laravel_accounting.fiscal_periods_count', 12);
            $monthsPerPeriod = 12 / $periodsCount;

            $periodNumber = 1;

            for ($i = 1; $i <= $periodsCount; $i++) {
                $periodEndDate = $startDate->copy()->addMonths($monthsPerPeriod)->subDay();

                if ($periodEndDate->gt($endDate)) {
                    $periodEndDate = $endDate->copy();
                }

                $period = FiscalPeriod::firstOrCreate([
                    'fiscal_year_id' => $year->id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $periodEndDate->toDateString(),
                ], [
                    'name' => $this->generatePeriodName($year, $periodNumber, $frequency, $startDate, $periodEndDate),
                    'is_closed' => false,
                    'is_locked' => false,
                ]);

                $periods[] = $period;

                $startDate = $periodEndDate->copy()->addDay();
                $periodNumber++;
            }

            return $periods;
        });
    }

    /**
     * Validate that a period can be closed
     */
    protected function validatePeriodCanBeClosed(FiscalPeriod $period): void
    {
        $unpostedEntries = JournalEntry::where('fiscal_period_id', $period->id)
            ->where('is_posted', false)
            ->where('is_voided', false)
            ->count();

        if ($unpostedEntries > 0) {
            throw new InvalidArgumentException(
                "Cannot close period with {$unpostedEntries} unposted journal entries"
            );
        }

        $trialBalance = $this->balanceService->verifyTrialBalance($period);

        if (! $trialBalance['is_balanced']) {
            throw new InvalidArgumentException(
                "Cannot close period with unbalanced trial balance. Difference: {$trialBalance['difference']}"
            );
        }
    }

    /**
     * Map fiscal_periods_count config to frequency
     */
    public function getFrequencyFromConfig(): string
    {
        $periodsCount = config('laravel_accounting.fiscal_periods_count', 12);

        return match ($periodsCount) {
            12 => 'monthly',
            4 => 'quarterly',
            2 => 'semi-annual',
            1 => 'yearly',
            default => 'custom',
        };
    }

    /**
     * Get months per period from config
     */
    public function getMonthsPerPeriod(): int
    {
        $periodsCount = config('laravel_accounting.fiscal_periods_count', 12);

        return (int) (12 / $periodsCount);
    }

    /**
     * Generate period name based on year and frequency
     */
    protected function generatePeriodName(FiscalYear $year, int $periodNumber, string $frequency, $startDate = null, $endDate = null): string
    {
        return match ($frequency) {
            'monthly' => $startDate ? $startDate->format('F Y') : sprintf('%s - Period %02d', $year->name, $periodNumber),
            'quarterly' => sprintf('%s - Q%d', $year->name, $periodNumber),
            'semi-annual' => sprintf('%s - H%d', $year->name, $periodNumber),
            'yearly' => $year->name,
            'custom' => $startDate && $endDate
                ? sprintf('Period %d (%s - %s)', $periodNumber, $startDate->format('M Y'), $endDate->format('M Y'))
                : sprintf('%s - Period %d', $year->name, $periodNumber),
            default => sprintf('%s - Period %d', $year->name, $periodNumber),
        };
    }

    /**
     * Ensure periods exist for current and upcoming fiscal years
     * This method can be called from a cron job to automatically create periods
     * @throws Throwable
     */
    public function ensurePeriodsExist(int $yearsAhead = 1): array
    {
        return DB::transaction(function () use ($yearsAhead) {
            $created = [];
            $fiscalYearStart = config('laravel_accounting.default_fiscal_year_start', [
                'month' => 1,
                'day' => 1,
            ]);
            $fiscalPeriodsCount = config('laravel_accounting.fiscal_periods_count', 1);

            $currentYear = now()->year;
            $startMonth = $fiscalYearStart['month'];
            $startDay = $fiscalYearStart['day'];

            for ($i = 0; $i <= $yearsAhead; $i++) {
                $yearStartDate = now()
                    ->setYear($currentYear + $i)
                    ->setMonth($startMonth)
                    ->setDay($startDay)
                    ->startOfDay();

                if ($yearStartDate->isFuture() && $i === 0) {
                    $yearStartDate->subYear();
                }

                $yearEndDate = $yearStartDate->copy()->addYear()->subDay();

                $fiscalYear = FiscalYear::firstOrCreate([
                    'start_date' => $yearStartDate->toDateString(),
                    'end_date' => $yearEndDate->toDateString(),
                ], [
                    'name' => $yearStartDate->year . '-' . $yearEndDate->year,
                    'is_closed' => false,
                ]);

                $existingPeriodsCount = $fiscalYear->fiscalPeriods()->count();
                $expectedPeriodsCount = $fiscalPeriodsCount;

                if ($existingPeriodsCount < $expectedPeriodsCount) {
                    $periods = $this->createPeriodsForYear($fiscalYear);
                    $created[$fiscalYear->name] = count($periods);
                }
            }

            return $created;
        });
    }

    /**
     * Get current open period
     */
    public function getCurrentPeriod(): ?FiscalPeriod
    {
        return FiscalPeriod::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('is_closed', false)
            ->first();
    }

    /**
     * Check if a date falls within an open period
     */
    public function isDateInOpenPeriod(\DateTimeInterface $date): bool
    {
        $period = FiscalPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        return $period && ! $period->is_closed && ! $period->is_locked;
    }
}
