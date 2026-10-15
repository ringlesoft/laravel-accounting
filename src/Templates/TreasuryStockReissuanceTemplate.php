<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

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
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $treasuryStockAccount = AccountPreset::TREASURY_STOCK,
        protected AccountPreset $additionalPaidInCapitalAccount = AccountPreset::ADDITIONAL_PAID_IN_CAPITAL,
        protected float $costBasis = 0.0,
    ) {}

    public function configure(): void
    {
        // Cash (reissue price) → Treasury Stock (at cost) + Additional Paid-in Capital (gain over cost)
        $this->debit($this->cashAccount);
        $this->credit($this->treasuryStockAccount, fn () => $this->costBasis);

        if ($this->costBasis > 0) {
            $this->credit($this->additionalPaidInCapitalAccount, fn ($amount) => $amount - $this->costBasis);
        }
    }

    public function validate(PostableModel $model): void
    {
        if ($this->costBasis < 0) {
            throw new InvalidVoucherException("costBasis must be >= 0, got {$this->costBasis}");
        }

        if ($this->costBasis > $model->getAmount()) {
            throw new InvalidVoucherException(
                "costBasis ({$this->costBasis}) cannot exceed the reissue proceeds ({$model->getAmount()}) - ".
                'reissuing below cost is a loss, which this template does not model.'
            );
        }
    }
}
