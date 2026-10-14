<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class LoanReceiptTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $loanPayableAccount = AccountPreset::LOAN_PAYABLE
    ) {}

    public function configure(): void
    {
        // Cash → Loan Payable
        $this->debit($this->cashAccount);
        $this->credit($this->loanPayableAccount);
    }
}
