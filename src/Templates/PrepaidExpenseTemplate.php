<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PrepaidExpenseTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $expenseAccount = AccountPreset::GENERAL_EXPENSE,
        protected AccountPreset $prepaidExpenseAccount = AccountPreset::PREPAID_EXPENSE
    ) {}

    public function configure(): void
    {
        // Expense → Prepaid Expense
        $this->debit($this->expenseAccount);
        $this->credit($this->prepaidExpenseAccount);
    }
}
