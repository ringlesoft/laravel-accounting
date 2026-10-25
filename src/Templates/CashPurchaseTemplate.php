<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

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
        $this->debit($this->expenseAccount, fn ($amount) => $amount / (1 + $this->taxRate));

        if ($this->taxRate > 0 && $this->inputTaxAccount) {
            $this->debit($this->inputTaxAccount, fn ($amount) => $amount * $this->taxRate / (1 + $this->taxRate));
        }

        $this->credit($this->cashAccount);
    }

    public function validate(PostableModel $model): void
    {
        if ($this->taxRate < 0) {
            throw new InvalidVoucherException("taxRate must be >= 0, got {$this->taxRate}");
        }
    }
}
