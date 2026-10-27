<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * Covers reissuing treasury stock at or above its cost basis (a gain,
 * credited to Additional Paid-in Capital). Reissuing below cost is a loss
 * and is debited against APIC/Retained Earnings instead - a different
 * posting direction this template doesn't model; build that one directly
 * via VoucherData.
 */
class TreasuryStockReissuanceTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $treasuryStockAccount = AccountPreset::TREASURY_STOCK,
        protected AccountPresetContract $additionalPaidInCapitalAccount = AccountPreset::ADDITIONAL_PAID_IN_CAPITAL,
        protected string|int|float $costBasis = 0,
    ) {}

    public function configure(): void
    {
        // Cash (reissue price) → Treasury Stock (at cost) + Additional Paid-in Capital (gain over cost)
        $this->debit($this->cashAccount);
        $this->credit($this->treasuryStockAccount, fn () => $this->roundAmount($this->costBasis));

        if (Money::isPositive($this->costBasis)) {
            $this->credit($this->additionalPaidInCapitalAccount, fn ($amount) => Money::sub($amount, $this->roundAmount($this->costBasis)));
        }
    }

    public function validate(PostableModel $model): void
    {
        if (Money::isNegative($this->costBasis)) {
            throw new InvalidVoucherException("costBasis must be >= 0, got {$this->costBasis}");
        }

        if (Money::compare($this->costBasis, $model->getPostableAmount()) > 0) {
            throw new InvalidVoucherException(
                "costBasis ({$this->costBasis}) cannot exceed the reissue proceeds ({$model->getPostableAmount()}) - ".
                'reissuing below cost is a loss, which this template does not model.'
            );
        }
    }
}
