<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

class PayrollAccrualTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $salaryExpenseAccount = AccountPreset::SALARIES_EXPENSE,
        protected AccountPresetContract $wagesPayableAccount = AccountPreset::WAGES_PAYABLE,
        protected ?AccountPresetContract $taxPayableAccount = AccountPreset::PAYROLL_TAX_PAYABLE,
        protected float $taxWithholdingRate = 0.0
    ) {}

    public function configure(): void
    {
        // Salary Expense → Wages Payable + Tax Payable
        $this->debit($this->salaryExpenseAccount);
        // Net wages are the remainder, so the entry balances exactly after rounding
        $this->credit($this->wagesPayableAccount, fn ($amount) => Money::sub($amount, Money::mul($amount, $this->taxWithholdingRate)));

        if ($this->taxWithholdingRate > 0 && $this->taxPayableAccount) {
            $this->credit($this->taxPayableAccount, fn ($amount) => Money::mul($amount, $this->taxWithholdingRate));
        }
    }

    public function validate(PostableModel $model): void
    {
        if ($this->taxWithholdingRate < 0 || $this->taxWithholdingRate > 1) {
            throw new InvalidVoucherException("taxWithholdingRate must be between 0 and 1, got {$this->taxWithholdingRate}");
        }
    }
}
