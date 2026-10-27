<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * The 5-line payroll payment: settles Wages Payable while also recognizing
 * employer payroll tax and withholding not yet remitted. Degrades to the
 * same 2-line shape as PayrollPaymentTemplate when both rates are 0.
 */
class PayrollPaymentDetailedTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $wagesPayableAccount = AccountPreset::WAGES_PAYABLE,
        protected AccountPresetContract $payrollTaxExpenseAccount = AccountPreset::PAYROLL_TAX_EXPENSE,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $payrollTaxPayableAccount = AccountPreset::PAYROLL_TAX_PAYABLE,
        protected ?AccountPresetContract $federalWithholdingPayableAccount = AccountPreset::FEDERAL_WITHHOLDING_PAYABLE,
        protected float $employerTaxRate = 0.0,
        protected float $withholdingRate = 0.0,
    ) {}

    public function configure(): void
    {
        // Wages Payable + Payroll Tax Expense → Cash (net of withholding) + Payroll Tax Payable + Federal Withholding Payable
        $this->debit($this->wagesPayableAccount);

        if ($this->employerTaxRate > 0) {
            $this->debit($this->payrollTaxExpenseAccount, fn ($amount) => Money::mul($amount, $this->employerTaxRate));
            $this->credit($this->payrollTaxPayableAccount, fn ($amount) => Money::mul($amount, $this->employerTaxRate));
        }

        // Net pay is the remainder, so the entry balances exactly after rounding
        $this->credit($this->cashAccount, fn ($amount) => Money::sub($amount, Money::mul($amount, $this->withholdingRate)));

        if ($this->withholdingRate > 0 && $this->federalWithholdingPayableAccount) {
            $this->credit($this->federalWithholdingPayableAccount, fn ($amount) => Money::mul($amount, $this->withholdingRate));
        }
    }

    public function validate(PostableModel $model): void
    {
        if ($this->employerTaxRate < 0) {
            throw new InvalidVoucherException("employerTaxRate must be >= 0, got {$this->employerTaxRate}");
        }

        if ($this->withholdingRate < 0 || $this->withholdingRate > 1) {
            throw new InvalidVoucherException("withholdingRate must be between 0 and 1, got {$this->withholdingRate}");
        }
    }
}
