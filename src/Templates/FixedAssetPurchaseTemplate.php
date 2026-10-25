<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class FixedAssetPurchaseTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $fixedAssetAccount = AccountPreset::FIXED_ASSETS,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected ?AccountPresetContract $payableAccount = AccountPreset::ACCOUNTS_PAYABLE,
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
