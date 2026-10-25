<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PaymentReceiptTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE
    ) {}

    public function configure(): void
    {
        // Cash → Accounts Receivable
        $this->debit($this->cashAccount);
        $this->credit($this->receivableAccount);
    }
}
