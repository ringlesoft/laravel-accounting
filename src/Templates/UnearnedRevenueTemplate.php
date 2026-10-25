<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class UnearnedRevenueTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $unearnedRevenueAccount = AccountPreset::UNEARNED_REVENUE
    ) {}

    public function configure(): void
    {
        // Cash → Unearned Revenue
        $this->debit($this->cashAccount);
        $this->credit($this->unearnedRevenueAccount);
    }
}
