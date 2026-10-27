<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * $model->getPostableAmount() is the bond's face value (what Bonds Payable is
 * credited for) - issuanceCosts is a flat amount deducted from the cash
 * actually received, not a rate.
 */
class BondIssuanceTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $bondIssuanceCostsAccount = AccountPreset::BOND_ISSUANCE_COSTS,
        protected AccountPresetContract $bondsPayableAccount = AccountPreset::BONDS_PAYABLE,
        protected string|int|float $issuanceCosts = 0,
    ) {}

    public function configure(): void
    {
        // Cash (net of issuance costs) + Bond Issuance Costs → Bonds Payable (face value)
        $this->debit($this->cashAccount, fn ($amount) => Money::sub($amount, $this->roundAmount($this->issuanceCosts)));

        if (Money::isPositive($this->issuanceCosts)) {
            $this->debit($this->bondIssuanceCostsAccount, fn () => $this->roundAmount($this->issuanceCosts));
        }

        $this->credit($this->bondsPayableAccount);
    }

    public function validate(PostableModel $model): void
    {
        if (Money::isNegative($this->issuanceCosts)) {
            throw new InvalidVoucherException("issuanceCosts must be >= 0, got {$this->issuanceCosts}");
        }

        if (Money::compare($this->issuanceCosts, $model->getPostableAmount()) > 0) {
            throw new InvalidVoucherException(
                "issuanceCosts ({$this->issuanceCosts}) cannot exceed the bond's face value ({$model->getPostableAmount()})"
            );
        }
    }
}
