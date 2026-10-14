<?php

namespace RingleSoft\LaravelAccounting\Listeners;

use RingleSoft\LaravelAccounting\Events\JournalEntryPosted;
use RingleSoft\LaravelAccounting\Events\JournalEntryReversed;
use RingleSoft\LaravelAccounting\Events\JournalEntryVoided;
use RingleSoft\LaravelAccounting\Models\AccountBalance;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Services\ReportService;

/**
 * Keeps cached balances and cached reports from going stale after a journal
 * entry posts, reverses, or voids. Invalidates rather than recomputes:
 * deletes the affected AccountBalance rows so BalanceService's
 * getOpeningBalance()/getClosingBalance() transparently fall back to a live
 * calculation on next read (cheap - no eager recalculation of every account
 * in the period), and clears the report cache outright since a report can
 * span any date range or set of accounts.
 */
class InvalidateAccountingCaches
{
    public function __construct(protected ReportService $reportService) {}

    public function handlePosted(JournalEntryPosted $event): void
    {
        $this->invalidate($event->journalEntry);
    }

    public function handleReversed(JournalEntryReversed $event): void
    {
        $this->invalidate($event->originalEntry);
        $this->invalidate($event->reversalEntry);
    }

    public function handleVoided(JournalEntryVoided $event): void
    {
        $this->invalidate($event->journalEntry);
    }

    protected function invalidate(JournalEntry $entry): void
    {
        if ($entry->fiscal_period_id) {
            $accountIds = $entry->items()->pluck('account_id')->unique();

            if ($accountIds->isNotEmpty()) {
                AccountBalance::where('fiscal_period_id', $entry->fiscal_period_id)
                    ->whereIn('account_id', $accountIds)
                    ->delete();
            }
        }

        $this->reportService->clearCache();
    }
}
