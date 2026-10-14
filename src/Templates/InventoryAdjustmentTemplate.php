<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class InventoryAdjustmentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $inventoryAccount = AccountPreset::INVENTORY,
        protected AccountPreset $shrinkageExpenseAccount = AccountPreset::INVENTORY_ADJUSTMENTS,
        protected bool $isIncrease = false
    ) {}

    public function configure(): void
    {
        // Inventory ↔ Shrinkage Expense
        if ($this->isIncrease) {
            // Increase inventory
            $this->debit($this->inventoryAccount);
            $this->credit($this->shrinkageExpenseAccount);
        } else {
            // Decrease inventory (shrinkage)
            $this->debit($this->shrinkageExpenseAccount);
            $this->credit($this->inventoryAccount);
        }
    }
}
