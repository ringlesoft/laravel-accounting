<?php

namespace RingleSoft\LaravelAccounting\Observers;

use RingleSoft\LaravelAccounting\Exceptions\PeriodClosedException;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;

class FiscalPeriodObserver
{
    /**
     * Handle the FiscalPeriod "creating" event.
     * Validate dates before creating
     */
    public function creating(FiscalPeriod $fiscalPeriod): bool
    {
        $this->validateDates($fiscalPeriod);
        $this->checkForOverlaps($fiscalPeriod);

        return true;
    }

    /**
     * Handle the FiscalPeriod "updating" event.
     * Validate dates and prevent changes to closed periods
     */
    public function updating(FiscalPeriod $fiscalPeriod): bool
    {
        if ($fiscalPeriod->is_locked) {
            $allowedFields = [];
            
            $dirtyFields = array_keys($fiscalPeriod->getDirty());
            $unauthorizedChanges = array_diff($dirtyFields, $allowedFields);

            if (!empty($unauthorizedChanges)) {
                throw new PeriodClosedException(
                    "Cannot modify locked fiscal period '{$fiscalPeriod->name}'. " .
                    "Attempted to modify: " . implode(', ', $unauthorizedChanges)
                );
            }
        }

        if ($fiscalPeriod->isDirty(['start_date', 'end_date'])) {
            if ($fiscalPeriod->is_closed) {
                throw new PeriodClosedException(
                    "Cannot change dates for closed fiscal period '{$fiscalPeriod->name}'."
                );
            }

            $this->validateDates($fiscalPeriod);
            $this->checkForOverlaps($fiscalPeriod);
        }

        return true;
    }

    /**
     * Handle the FiscalPeriod "deleting" event.
     * Prevent deletion of periods with transactions
     */
    public function deleting(FiscalPeriod $fiscalPeriod): bool
    {
        if ($fiscalPeriod->is_closed || $fiscalPeriod->is_locked) {
            throw new PeriodClosedException(
                "Cannot delete closed or locked fiscal period '{$fiscalPeriod->name}'."
            );
        }

        $transactionCount = $fiscalPeriod->journalEntries()->count();

        if ($transactionCount > 0) {
            throw new PeriodClosedException(
                "Cannot delete fiscal period '{$fiscalPeriod->name}'. " .
                "This period has {$transactionCount} journal entry(ies)."
            );
        }

        return true;
    }

    /**
     * Validate that start_date is before end_date
     */
    protected function validateDates(FiscalPeriod $fiscalPeriod): void
    {
        if ($fiscalPeriod->start_date >= $fiscalPeriod->end_date) {
            throw new \InvalidArgumentException(
                "Fiscal period start date must be before end date. " .
                "Start: {$fiscalPeriod->start_date->toDateString()}, " .
                "End: {$fiscalPeriod->end_date->toDateString()}"
            );
        }

        $maxDuration = 366;
        $duration = $fiscalPeriod->start_date->diffInDays($fiscalPeriod->end_date);
        
        if ($duration > $maxDuration) {
            throw new \InvalidArgumentException(
                "Fiscal period duration cannot exceed {$maxDuration} days. " .
                "Current duration: {$duration} days."
            );
        }
    }

    /**
     * Check for overlapping periods in the same fiscal year
     */
    protected function checkForOverlaps(FiscalPeriod $fiscalPeriod): void
    {
        $query = FiscalPeriod::where('fiscal_year_id', $fiscalPeriod->fiscal_year_id)
            ->where(function ($q) use ($fiscalPeriod) {
                $q->whereBetween('start_date', [$fiscalPeriod->start_date, $fiscalPeriod->end_date])
                    ->orWhereBetween('end_date', [$fiscalPeriod->start_date, $fiscalPeriod->end_date])
                    ->orWhere(function ($q2) use ($fiscalPeriod) {
                        $q2->where('start_date', '<=', $fiscalPeriod->start_date)
                            ->where('end_date', '>=', $fiscalPeriod->end_date);
                    });
            });

        if ($fiscalPeriod->exists) {
            $query->where('id', '!=', $fiscalPeriod->id);
        }

        $overlapping = $query->first();

        if ($overlapping) {
            throw new \InvalidArgumentException(
                "Fiscal period dates overlap with existing period '{$overlapping->name}' " .
                "({$overlapping->start_date->toDateString()} to {$overlapping->end_date->toDateString()})."
            );
        }
    }
}
