<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PayrollPaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $wagesPayableAccount = AccountPreset::WAGES_PAYABLE,
        protected AccountPreset $cashAccount = AccountPreset::CASH
    ) {}

    public function configure(): void
    {
        // Wages Payable → Cash
        $this->debit($this->wagesPayableAccount);
        $this->credit($this->cashAccount);
    }
}
