<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

class SalesDiscountTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $salesDiscountAccount = AccountPreset::SALES_DISCOUNTS,
        protected AccountPresetContract $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE,
        protected float $discountRate = 0.0,
    ) {}

    public function configure(): void
    {
        // Cash (net of discount) + Sales Discount → Accounts Receivable (full)
        // Net cash is the remainder, so the entry balances exactly after rounding
        $this->debit($this->cashAccount, fn ($amount) => Money::sub($amount, $this->roundAmount(Money::mul($amount, $this->discountRate))));

        if ($this->discountRate > 0) {
            $this->debit($this->salesDiscountAccount, fn ($amount) => $this->roundAmount(Money::mul($amount, $this->discountRate)));
        }

        $this->credit($this->receivableAccount);
    }

    public function validate(PostableModel $model): void
    {
        if ($this->discountRate < 0 || $this->discountRate > 1) {
            throw new InvalidVoucherException("discountRate must be between 0 and 1, got {$this->discountRate}");
        }
    }
}
