<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class BadDebtTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $badDebtExpenseAccount = AccountPreset::BAD_DEBT_EXPENSE,
        protected AccountPresetContract $allowanceAccount = AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS
    ) {}

    public function configure(): void
    {
        // Bad Debt Expense → Allowance
        $this->debit($this->badDebtExpenseAccount);
        $this->credit($this->allowanceAccount);
    }
}
