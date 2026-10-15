<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

/**
 * Records a realized FX gain whose amount is already known
 * ($model->getAmount()) - this template doesn't calculate it from exchange
 * rates. Defaults to debiting Cash; override $cashOrReceivableAccount to
 * ACCOUNTS_RECEIVABLE if the gain arose from revaluing an open receivable
 * rather than settling in cash.
 */
class ForeignExchangeGainTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashOrReceivableAccount = AccountPreset::CASH,
        protected AccountPreset $gainAccount = AccountPreset::FOREIGN_EXCHANGE_GAIN,
    ) {}

    public function configure(): void
    {
        // Cash/Accounts Receivable → Foreign Exchange Gain
        $this->debit($this->cashOrReceivableAccount);
        $this->credit($this->gainAccount);
    }
}
