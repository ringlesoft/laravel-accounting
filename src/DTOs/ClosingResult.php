<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * The result of ClosingService::commit() - the actual posted JournalEntry
 * for each closing step, or null for a step that had nothing to close and
 * was skipped. Every non-null entry here was created and posted through
 * the normal JournalService::createDraft()/post() path. Totals are
 * Money::SCALE numeric strings.
 */
class ClosingResult
{
    public function __construct(
        public FiscalPeriod $period,
        public ?JournalEntry $closeRevenueEntry = null,
        public ?JournalEntry $closeExpensesEntry = null,
        public ?JournalEntry $closeIncomeSummaryEntry = null,
        public ?JournalEntry $closeDividendsEntry = null,
        public string $totalRevenue = Money::ZERO,
        public string $totalExpenses = Money::ZERO,
        public string $netIncome = Money::ZERO,
        public string $dividendsDeclared = Money::ZERO,
    ) {}

    public function isProfit(): bool
    {
        return ! Money::isNegative($this->netIncome);
    }

    public function hasNothingClosed(): bool
    {
        return $this->closeRevenueEntry === null
            && $this->closeExpensesEntry === null
            && $this->closeIncomeSummaryEntry === null
            && $this->closeDividendsEntry === null;
    }

    public function toArray(): array
    {
        return [
            'period_id' => $this->period->id,
            'period_name' => $this->period->name,
            'close_revenue_entry_id' => $this->closeRevenueEntry?->id,
            'close_expenses_entry_id' => $this->closeExpensesEntry?->id,
            'close_income_summary_entry_id' => $this->closeIncomeSummaryEntry?->id,
            'close_dividends_entry_id' => $this->closeDividendsEntry?->id,
            'total_revenue' => $this->totalRevenue,
            'total_expenses' => $this->totalExpenses,
            'net_income' => $this->netIncome,
            'dividends_declared' => $this->dividendsDeclared,
            'is_profit' => $this->isProfit(),
            'has_nothing_closed' => $this->hasNothingClosed(),
        ];
    }
}
