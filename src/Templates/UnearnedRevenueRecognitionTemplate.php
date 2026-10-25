<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

/**
 * The missing counterpart to UnearnedRevenueTemplate: that one covers
 * receiving the advance, this one covers recognizing it as earned once
 * delivered.
 */
class UnearnedRevenueRecognitionTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $unearnedRevenueAccount = AccountPreset::UNEARNED_REVENUE,
        protected AccountPresetContract $revenueAccount = AccountPreset::SALES_REVENUE,
    ) {}

    public function configure(): void
    {
        // Unearned Revenue → Sales Revenue
        $this->debit($this->unearnedRevenueAccount);
        $this->credit($this->revenueAccount);
    }
}
