<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class FixedAssetPurchaseTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $fixedAssetAccount = AccountPreset::FIXED_ASSETS,
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected ?AccountPreset $payableAccount = AccountPreset::ACCOUNTS_PAYABLE,
        protected bool $isCashPurchase = true
    ) {}

    public function configure(): void
    {
        // Fixed Asset → Cash/AP
        $this->debit($this->fixedAssetAccount);
        
        if ($this->isCashPurchase) {
            $this->credit($this->cashAccount);
        } else {
            $this->credit($this->payableAccount);
        }
    }
}
