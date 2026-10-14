<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class UnearnedRevenueTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $unearnedRevenueAccount = AccountPreset::UNEARNED_REVENUE
    ) {}

    public function configure(): void
    {
        // Cash → Unearned Revenue
        $this->debit($this->cashAccount);
        $this->credit($this->unearnedRevenueAccount);
    }
}
