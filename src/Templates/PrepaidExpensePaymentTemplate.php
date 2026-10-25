<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PrepaidExpensePaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $prepaidExpenseAccount = AccountPreset::PREPAID_EXPENSES,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH
    ) {}

    public function configure(): void
    {
        // Prepaid Expense → Cash (initial prepayment)
        $this->debit($this->prepaidExpenseAccount);
        $this->credit($this->cashAccount);
    }
}
