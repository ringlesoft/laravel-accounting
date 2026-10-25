<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class OwnerInvestmentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $equityAccount = AccountPreset::OWNERS_EQUITY
    ) {}

    public function configure(): void
    {
        // Cash → Owner's Equity
        $this->debit($this->cashAccount);
        $this->credit($this->equityAccount);
    }
}
