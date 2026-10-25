<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class DepreciationTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $depreciationExpenseAccount = AccountPreset::DEPRECIATION_EXPENSE,
        protected AccountPresetContract $accumulatedDepreciationAccount = AccountPreset::ACCUMULATED_DEPRECIATION
    ) {}

    public function configure(): void
    {
        // Depreciation Expense → Accumulated Depreciation
        $this->debit($this->depreciationExpenseAccount);
        $this->credit($this->accumulatedDepreciationAccount);
    }
}
