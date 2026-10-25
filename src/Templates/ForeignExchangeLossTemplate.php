<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

/**
 * Records a realized FX loss whose amount is already known
 * ($model->getPostableAmount()) - this template doesn't calculate it from exchange
 * rates. Defaults to crediting Cash; override $cashOrPayableAccount to
 * ACCOUNTS_PAYABLE if the loss arose from revaluing an open payable rather
 * than settling in cash.
 */
class ForeignExchangeLossTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $lossAccount = AccountPreset::FOREIGN_EXCHANGE_LOSS,
        protected AccountPresetContract $cashOrPayableAccount = AccountPreset::CASH,
    ) {}

    public function configure(): void
    {
        // Foreign Exchange Loss → Cash/Accounts Payable
        $this->debit($this->lossAccount);
        $this->credit($this->cashOrPayableAccount);
    }
}
