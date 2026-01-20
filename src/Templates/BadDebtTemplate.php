<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class BadDebtTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $badDebtExpenseAccount = AccountPreset::BAD_DEBT_EXPENSE,
        protected AccountPreset $allowanceAccount = AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS
    ) {}

    public function configure(): void
    {
        // Bad Debt Expense → Allowance
        $this->debit($this->badDebtExpenseAccount);
        $this->credit($this->allowanceAccount);
    }
}
