<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PayrollExpenseTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $salaryExpenseAccount = AccountPreset::SALARIES_EXPENSE,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH
    ) {}

    public function configure(): void
    {
        // Salary Expense → Cash (direct expense-and-pay, no accrual)
        $this->debit($this->salaryExpenseAccount);
        $this->credit($this->cashAccount);
    }
}
