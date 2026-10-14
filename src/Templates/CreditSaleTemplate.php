<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class CreditSaleTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE,
        protected AccountPreset $revenueAccount = AccountPreset::SALES_REVENUE,
        protected ?AccountPreset $taxAccount = AccountPreset::SALES_TAX_PAYABLE,
        protected float $taxRate = 0.0
    ) {}

    public function configure(): void
    {
        $this->debit($this->receivableAccount);
        $this->credit($this->revenueAccount, fn ($amount) => ($amount / (1 + $this->taxRate)));

        if ($this->taxRate > 0 && $this->taxAccount) {
            $this->credit($this->taxAccount, fn ($amount) => ($amount * $this->taxRate / (1 + $this->taxRate)));
        }
    }

    public function validate(PostableModel $model): void
    {
        if ($this->taxRate < 0) {
            throw new InvalidVoucherException("taxRate must be >= 0, got {$this->taxRate}");
        }
    }
}
