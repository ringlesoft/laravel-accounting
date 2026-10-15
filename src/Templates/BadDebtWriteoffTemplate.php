<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;

/**
 * Writes off one customer's specific uncollectible balance against the
 * allowance already estimated by BadDebtTemplate - that one only covers
 * the estimation step, this one covers the actual write-off.
 */
class BadDebtWriteoffTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $allowanceAccount = AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS,
        protected AccountPreset $receivableAccount = AccountPreset::ACCOUNTS_RECEIVABLE,
    ) {}

    public function configure(): void
    {
        // Allowance for Doubtful Accounts → Accounts Receivable
        $this->debit($this->allowanceAccount);
        $this->credit($this->receivableAccount);
    }
}
