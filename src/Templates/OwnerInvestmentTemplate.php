<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class OwnerInvestmentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $equityAccount = AccountPreset::OWNERS_EQUITY
    ) {}

    public function configure(): void
    {
        // Cash → Owner's Equity
        $this->debit($this->cashAccount);
        $this->credit($this->equityAccount);
    }
}
