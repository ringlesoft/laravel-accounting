<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class CashPurchaseTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $expenseAccount = AccountPreset::COST_OF_GOODS_SOLD,
        protected ?AccountPreset $inputTaxAccount = AccountPreset::INPUT_VAT,
        protected AccountPreset $cashAccount = AccountPreset::CASH,
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
}
