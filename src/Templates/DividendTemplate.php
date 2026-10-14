<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class DividendTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $dividendsAccount = AccountPreset::DIVIDENDS_DECLARED,
        protected AccountPreset $cashAccount = AccountPreset::CASH
    ) {}

    public function configure(): void
    {
        // Dividends Declared → Cash
        $this->debit($this->dividendsAccount);
        $this->credit($this->cashAccount);
    }
}
