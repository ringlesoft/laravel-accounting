<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

/**
 * {@inheritDoc}
 */
class AccruedRevenueTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $accruedRevenueAccount = AccountPreset::ACCRUED_REVENUE,
        protected AccountPreset $revenueAccount = AccountPreset::SALES_REVENUE
    ) {}

    public function configure(): void
    {
        // Accrued Revenue → Revenue
        $this->debit($this->accruedRevenueAccount);
        $this->credit($this->revenueAccount);
    }
}
