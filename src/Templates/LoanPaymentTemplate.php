<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

class LoanPaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $loanPayableAccount = AccountPreset::LOAN_PAYABLE,
        protected AccountPresetContract $interestExpenseAccount = AccountPreset::INTEREST_EXPENSE,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected string|int|float $interestAmount = 0
    ) {}

    public function configure(): void
    {
        // Loan Payable + Interest Expense → Cash
        $this->debit($this->loanPayableAccount, fn ($amount) => Money::sub($amount, $this->interestAmount));

        if (Money::isPositive($this->interestAmount)) {
            $this->debit($this->interestExpenseAccount, fn () => $this->interestAmount);
        }

        $this->credit($this->cashAccount);
    }

    public function validate(PostableModel $model): void
    {
        if (Money::isNegative($this->interestAmount)) {
            throw new InvalidVoucherException("interestAmount must be >= 0, got {$this->interestAmount}");
        }

        if (Money::compare($this->interestAmount, $model->getPostableAmount()) > 0) {
            throw new InvalidVoucherException(
                "interestAmount ({$this->interestAmount}) cannot exceed the total payment amount ({$model->getPostableAmount()})"
            );
        }
    }
}
