<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PaymentReceiptTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE
    ) {}

    public function configure(): void
    {
        // Cash → Accounts Receivable
        $this->debit($this->cashAccount);
        $this->credit($this->receivableAccount);
    }
}
