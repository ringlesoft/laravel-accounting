<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class SalesReturnTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $salesReturnsAccount = AccountPreset::SALES_RETURNS,
        protected AccountPresetContract $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE,
        protected ?AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected bool $isCashReturn = false
    ) {}

    public function configure(): void
    {
        // Sales Returns → AR/Cash
        $this->debit($this->salesReturnsAccount);

        if ($this->isCashReturn) {
            $this->credit($this->cashAccount);
        } else {
            $this->credit($this->receivableAccount);
        }
    }
}
