<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

class CashPurchaseTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $expenseAccount = AccountPreset::COST_OF_GOODS_SOLD,
        protected ?AccountPresetContract $inputTaxAccount = AccountPreset::INPUT_VAT,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected float $taxRate = 0,
    ) {}

    public function configure(): void
    {
        // Inventory/Expense + Input VAT → Cash
        $this->debit($this->expenseAccount, fn ($amount) => $this->netOfTax($amount));

        if ($this->taxRate > 0 && $this->inputTaxAccount) {
            // The tax is the remainder, so the entry balances exactly after rounding
            $this->debit($this->inputTaxAccount, fn ($amount) => Money::sub($amount, $this->netOfTax($amount)));
        }

        $this->credit($this->cashAccount);
    }

    protected function netOfTax(string $amount): string
    {
        return Money::div($amount, 1 + $this->taxRate);
    }

    public function validate(PostableModel $model): void
    {
        if ($this->taxRate < 0) {
            throw new InvalidVoucherException("taxRate must be >= 0, got {$this->taxRate}");
        }
    }
}
