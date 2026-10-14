<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class PayrollAccrualTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $salaryExpenseAccount = AccountPreset::SALARIES_EXPENSE,
        protected AccountPreset $wagesPayableAccount = AccountPreset::WAGES_PAYABLE,
        protected ?AccountPreset $taxPayableAccount = AccountPreset::PAYROLL_TAX_PAYABLE,
        protected float $taxWithholdingRate = 0.0
    ) {}

    public function configure(): void
    {
        // Salary Expense → Wages Payable + Tax Payable
        $this->debit($this->salaryExpenseAccount);
        $this->credit($this->wagesPayableAccount, fn ($amount) => $amount * (1 - $this->taxWithholdingRate));

        if ($this->taxWithholdingRate > 0 && $this->taxPayableAccount) {
            $this->credit($this->taxPayableAccount, fn ($amount) => $amount * $this->taxWithholdingRate);
        }
    }

    public function validate(PostableModel $model): void
    {
        if ($this->taxWithholdingRate < 0 || $this->taxWithholdingRate > 1) {
            throw new InvalidVoucherException("taxWithholdingRate must be between 0 and 1, got {$this->taxWithholdingRate}");
        }
    }
}
