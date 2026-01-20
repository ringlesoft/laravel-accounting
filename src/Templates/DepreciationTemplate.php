<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class DepreciationTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $depreciationExpenseAccount = AccountPreset::DEPRECIATION_EXPENSE,
        protected AccountPreset $accumulatedDepreciationAccount = AccountPreset::ACCUMULATED_DEPRECIATION
    ) {}

    public function configure(): void
    {
        // Depreciation Expense → Accumulated Depreciation
        $this->debit($this->depreciationExpenseAccount);
        $this->credit($this->accumulatedDepreciationAccount);
    }
}
