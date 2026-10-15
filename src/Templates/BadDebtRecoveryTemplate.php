<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

/**
 * Collects a previously written-off debt. Defaults to reinstating the
 * allowance (the standard treatment); override $allowanceAccount to
 * BAD_DEBT_EXPENSE instead if your policy credits recoveries straight to
 * the expense account rather than the allowance.
 */
class BadDebtRecoveryTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $allowanceAccount = AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS,
    ) {}

    public function configure(): void
    {
        // Cash → Allowance for Doubtful Accounts
        $this->debit($this->cashAccount);
        $this->credit($this->allowanceAccount);
    }
}
