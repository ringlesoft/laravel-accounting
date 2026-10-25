<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PurchaseReturnTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $payableAccount = AccountPreset::ACCOUNTS_PAYABLE,
        protected ?AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $inventoryAccount = AccountPreset::INVENTORY,
        protected bool $isCashReturn = false
    ) {}

    public function configure(): void
    {
        // AP/Cash → Inventory/Expense
        if ($this->isCashReturn) {
            $this->debit($this->cashAccount);
        } else {
            $this->debit($this->payableAccount);
        }

        $this->credit($this->inventoryAccount);
    }
}
