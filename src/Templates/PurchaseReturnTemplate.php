<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class PurchaseReturnTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $payableAccount = AccountPreset::ACCOUNTS_PAYABLE,
        protected ?AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $inventoryAccount = AccountPreset::INVENTORY,
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
