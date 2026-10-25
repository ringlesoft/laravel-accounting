<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PrepaidExpenseTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $expenseAccount = AccountPreset::OPERATING_EXPENSES,
        protected AccountPresetContract $prepaidExpenseAccount = AccountPreset::PREPAID_EXPENSES
    ) {}

    public function configure(): void
    {
        // Expense → Prepaid Expense
        $this->debit($this->expenseAccount);
        $this->credit($this->prepaidExpenseAccount);
    }
}
