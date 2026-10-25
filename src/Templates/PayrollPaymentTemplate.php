<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PayrollPaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $wagesPayableAccount = AccountPreset::WAGES_PAYABLE,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH
    ) {}

    public function configure(): void
    {
        // Wages Payable → Cash
        $this->debit($this->wagesPayableAccount);
        $this->credit($this->cashAccount);
    }
}
