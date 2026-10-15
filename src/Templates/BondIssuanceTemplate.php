<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

/**
 * $model->getPostableAmount() is the bond's face value (what Bonds Payable is
 * credited for) - issuanceCosts is a flat amount deducted from the cash
 * actually received, not a rate.
 */
class BondIssuanceTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $bondIssuanceCostsAccount = AccountPreset::BOND_ISSUANCE_COSTS,
        protected AccountPreset $bondsPayableAccount = AccountPreset::BONDS_PAYABLE,
        protected float $issuanceCosts = 0.0,
    ) {}

    public function configure(): void
    {
        // Cash (net of issuance costs) + Bond Issuance Costs → Bonds Payable (face value)
        $this->debit($this->cashAccount, fn ($amount) => $amount - $this->issuanceCosts);

        if ($this->issuanceCosts > 0) {
            $this->debit($this->bondIssuanceCostsAccount, fn () => $this->issuanceCosts);
        }

        $this->credit($this->bondsPayableAccount);
    }

    public function validate(PostableModel $model): void
    {
        if ($this->issuanceCosts < 0) {
            throw new InvalidVoucherException("issuanceCosts must be >= 0, got {$this->issuanceCosts}");
        }

        if ($this->issuanceCosts > $model->getPostableAmount()) {
            throw new InvalidVoucherException(
                "issuanceCosts ({$this->issuanceCosts}) cannot exceed the bond's face value ({$model->getPostableAmount()})"
            );
        }
    }
}
