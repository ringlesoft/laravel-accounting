<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class DividendTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $retainedEarningsAccount = AccountPreset::RETAINED_EARNINGS,
        protected AccountPreset $cashAccount = AccountPreset::CASH
    ) {}

    public function configure(): void
    {
        // Retained Earnings → Cash
        $this->debit($this->retainedEarningsAccount);
        $this->credit($this->cashAccount);
    }
}
