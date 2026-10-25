<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class CashSaleTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $revenueAccount = AccountPreset::SALES_REVENUE,
        protected ?AccountPresetContract $taxAccount = AccountPreset::SALES_TAX_PAYABLE,
        protected float $taxRate = 0.0
    ) {}

    public function configure(): void
    {
        // Cash → Sales Revenue (+ Tax Payable)
        $this->debit($this->cashAccount)
            ->credit($this->revenueAccount, fn ($amount) => $amount / (1 + $this->taxRate));

        if ($this->taxRate > 0 && $this->taxAccount) {
            $this->credit($this->taxAccount, fn ($amount) => $amount * $this->taxRate / (1 + $this->taxRate));
        }
    }

    public function validate(PostableModel $model): void
    {
        if ($this->taxRate < 0) {
            throw new InvalidVoucherException("taxRate must be >= 0, got {$this->taxRate}");
        }
    }
}
