<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class CashSaleTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $revenueAccount = AccountPreset::SALES_REVENUE,
        protected ?AccountPreset $taxAccount = AccountPreset::SALES_TAX_PAYABLE,
        protected float $taxRate = 0.0
    ) {}

    public function configure(): void
    {
        // Cash → Sales Revenue (+ Tax Payable)
        $this->debit($this->cashAccount);
        $this->credit($this->revenueAccount, fn ($amount) => $amount / (1 + $this->taxRate));

        if ($this->taxRate > 0 && $this->taxAccount) {
            $this->credit($this->taxAccount, fn ($amount) => $amount * $this->taxRate / (1 + $this->taxRate));
        }
    }
}
