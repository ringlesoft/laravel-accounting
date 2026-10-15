<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class SalesDiscountTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $salesDiscountAccount = AccountPreset::SALES_DISCOUNTS,
        protected AccountPreset $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE,
        protected float $discountRate = 0.0,
    ) {}

    public function configure(): void
    {
        // Cash (net of discount) + Sales Discount → Accounts Receivable (full)
        $this->debit($this->cashAccount, fn ($amount) => $amount * (1 - $this->discountRate));

        if ($this->discountRate > 0) {
            $this->debit($this->salesDiscountAccount, fn ($amount) => $amount * $this->discountRate);
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
