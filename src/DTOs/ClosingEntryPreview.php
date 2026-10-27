<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * The result of ClosingService::preview() - the exact VoucherData that would
 * be posted for each closing step, or null for a step with nothing to close.
 * Nothing in this DTO has been persisted. Totals are Money::SCALE numeric strings.
 */
class ClosingEntryPreview
{
    public function __construct(
        public FiscalPeriod $period,
        public ?VoucherData $closeRevenue = null,
        public ?VoucherData $closeExpenses = null,
        public ?VoucherData $closeIncomeSummary = null,
        public ?VoucherData $closeDividends = null,
        public string $totalRevenue = Money::ZERO,
        public string $totalExpenses = Money::ZERO,
        public string $netIncome = Money::ZERO,
        public string $dividendsDeclared = Money::ZERO,
    ) {}

    public function isProfit(): bool
    {
        return ! Money::isNegative($this->netIncome);
    }

    public function hasNothingToClose(): bool
    {
        return $this->closeRevenue === null
            && $this->closeExpenses === null
            && $this->closeIncomeSummary === null
            && $this->closeDividends === null;
    }

    public function toArray(): array
    {
        return [
            'period_id' => $this->period->id,
            'period_name' => $this->period->name,
            'close_revenue' => $this->closeRevenue?->toArray(),
            'close_expenses' => $this->closeExpenses?->toArray(),
            'close_income_summary' => $this->closeIncomeSummary?->toArray(),
            'close_dividends' => $this->closeDividends?->toArray(),
            'total_revenue' => $this->totalRevenue,
            'total_expenses' => $this->totalExpenses,
            'net_income' => $this->netIncome,
            'dividends_declared' => $this->dividendsDeclared,
            'is_profit' => $this->isProfit(),
            'has_nothing_to_close' => $this->hasNothingToClose(),
        ];
    }
}
