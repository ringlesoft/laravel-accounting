<?php

namespace RingleSoft\LaravelAccounting\Services;

use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Exceptions\PeriodClosedException;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\FiscalYear;
use RingleSoft\LaravelAccounting\Models\JournalEntry;

class PeriodService
{
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Open a fiscal period
     */
    public function openPeriod(FiscalPeriod $period, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($period, $userId) {
            if (!$period->is_closed) {
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
     */
    public function lockPeriod(FiscalPeriod $period, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($period, $userId) {
            if (!$period->is_closed) {
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
        return DB::transaction(function () use ($period, $userId) {
            if (!$period->is_locked) {
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
     */
    public function rollForwardBalances(FiscalPeriod $from, FiscalPeriod $to): void
    {
        DB::transaction(function () use ($from, $to) {
            if (!$from->is_closed) {
                throw new \InvalidArgumentException('Source period must be closed before rolling forward balances');
            }

            $this->balanceService->updateCachedBalances($from);
            $this->balanceService->updateCachedBalances($to);
        });
    }

    /**
     * Create fiscal periods for a fiscal year
     */
    public function createPeriodsForYear(FiscalYear $year, string $frequency = 'monthly'): array
    {
        return DB::transaction(function () use ($year, $frequency) {
            $periods = [];
            $startDate = $year->start_date->copy();
            $endDate = $year->end_date;

            $periodNumber = 1;

            while ($startDate->lte($endDate)) {
                $periodEndDate = match ($frequency) {
                    'monthly' => $startDate->copy()->endOfMonth(),
                    'quarterly' => $startDate->copy()->addMonths(3)->subDay(),
                    'yearly' => $endDate->copy(),
                    default => throw new \InvalidArgumentException("Invalid frequency: {$frequency}"),
                };

                if ($periodEndDate->gt($endDate)) {
                    $periodEndDate = $endDate->copy();
                }

                $period = FiscalPeriod::firstOrCreate([
                    'fiscal_year_id' => $year->id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $periodEndDate->toDateString(),
                ], [
                    'name' => $this->generatePeriodName($year, $periodNumber, $frequency),
                    'is_closed' => false,
                    'is_locked' => false,
                ]);

                $periods[] = $period;

                $startDate = $periodEndDate->copy()->addDay();
                $periodNumber++;

                if ($frequency === 'yearly') {
                    break;
                }
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
            throw new \InvalidArgumentException(
                "Cannot close period with {$unpostedEntries} unposted journal entries"
            );
        }

        $trialBalance = $this->balanceService->verifyTrialBalance($period);

        if (!$trialBalance['is_balanced']) {
            throw new \InvalidArgumentException(
                "Cannot close period with unbalanced trial balance. Difference: {$trialBalance['difference']}"
            );
        }
    }

    /**
     * Generate period name based on year and frequency
     */
    protected function generatePeriodName(FiscalYear $year, int $periodNumber, string $frequency): string
    {
        return match ($frequency) {
            'monthly' => sprintf('%s - Period %02d', $year->name, $periodNumber),
            'quarterly' => sprintf('%s - Q%d', $year->name, $periodNumber),
            'yearly' => $year->name,
            default => sprintf('%s - Period %d', $year->name, $periodNumber),
        };
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

        return $period && !$period->is_closed && !$period->is_locked;
    }
}
