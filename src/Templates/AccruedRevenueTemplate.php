<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

/**
 * {@inheritDoc}
 */
class AccruedRevenueTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $accruedRevenueAccount = AccountPreset::ACCRUED_REVENUE,
        protected AccountPresetContract $revenueAccount = AccountPreset::SALES_REVENUE
    ) {}

    public function configure(): void
    {
        // Accrued Revenue → Revenue
        $this->debit($this->accruedRevenueAccount);
        $this->credit($this->revenueAccount);
    }
}
