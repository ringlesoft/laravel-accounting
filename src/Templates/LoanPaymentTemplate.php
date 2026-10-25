<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class LoanPaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $loanPayableAccount = AccountPreset::LOAN_PAYABLE,
        protected AccountPresetContract $interestExpenseAccount = AccountPreset::INTEREST_EXPENSE,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
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

    public function validate(PostableModel $model): void
    {
        if ($this->interestAmount < 0) {
            throw new InvalidVoucherException("interestAmount must be >= 0, got {$this->interestAmount}");
        }

        if ($this->interestAmount > $model->getPostableAmount()) {
            throw new InvalidVoucherException(
                "interestAmount ({$this->interestAmount}) cannot exceed the total payment amount ({$model->getPostableAmount()})"
            );
        }
    }
}
