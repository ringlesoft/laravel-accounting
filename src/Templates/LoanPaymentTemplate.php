<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class LoanPaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $loanPayableAccount = AccountPreset::LOAN_PAYABLE,
        protected AccountPreset $interestExpenseAccount = AccountPreset::INTEREST_EXPENSE,
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected float $interestAmount = 0.0
    ) {}

    public function configure(): void
    {
        // Loan Payable + Interest Expense → Cash
        $this->debit($this->loanPayableAccount, fn ($amount) => $amount - $this->interestAmount);

        if ($this->interestAmount > 0) {
            $this->debit($this->interestExpenseAccount, fn ($amount) => $this->interestAmount);
        }

        $this->credit($this->cashAccount);
    }
}
