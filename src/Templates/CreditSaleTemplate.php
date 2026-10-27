<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

class CreditSaleTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE,
        protected AccountPresetContract $revenueAccount = AccountPreset::SALES_REVENUE,
        protected ?AccountPresetContract $taxAccount = AccountPreset::SALES_TAX_PAYABLE,
        protected float $taxRate = 0.0
    ) {}

    public function configure(): void
    {
        $this->debit($this->receivableAccount);
        $this->credit($this->revenueAccount, fn ($amount) => $this->netOfTax($amount));

        if ($this->taxRate > 0 && $this->taxAccount) {
            // The tax is the remainder, so the entry balances exactly after rounding
            $this->credit($this->taxAccount, fn ($amount) => Money::sub($amount, $this->netOfTax($amount)));
        }
    }

    protected function netOfTax(string $amount): string
    {
        return $this->roundAmount(Money::div($amount, 1 + $this->taxRate));
    }

    public function validate(PostableModel $model): void
    {
        if ($this->taxRate < 0) {
            throw new InvalidVoucherException("taxRate must be >= 0, got {$this->taxRate}");
        }
    }
}
