<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class BillPaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $payableAccount = AccountPreset::ACCOUNTS_PAYABLE,
        protected AccountPreset $cashAccount = AccountPreset::CASH
    ) {}

    public function configure(): void
    {
        // Accounts Payable → Cash
        $this->debit($this->payableAccount);
        $this->credit($this->cashAccount);
    }
}
