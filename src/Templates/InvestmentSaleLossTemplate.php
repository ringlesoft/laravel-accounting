<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

/**
 * $model->getPostableAmount() is the cash proceeds received. $costBasis is the
 * investment's own cost, known to the caller beforehand - this template
 * never queries it. Covers only the case where proceeds are below cost (a
 * loss); use InvestmentSaleGainTemplate for a gain.
 */
class InvestmentSaleLossTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $investmentAccount = AccountPreset::SHORT_TERM_INVESTMENTS,
        protected AccountPresetContract $lossAccount = AccountPreset::LOSS_ON_SALE_OF_ASSETS,
        protected float $costBasis = 0.0,
    ) {}

    public function configure(): void
    {
        // Cash (proceeds) + Loss on Sale → Investment (at cost)
        $this->debit($this->cashAccount);
        $this->debit($this->lossAccount, fn ($amount) => $this->costBasis - $amount);
        $this->credit($this->investmentAccount, fn () => $this->costBasis);
    }

    public function validate(PostableModel $model): void
    {
        if ($this->costBasis < 0) {
            throw new InvalidVoucherException("costBasis must be >= 0, got {$this->costBasis}");
        }

        if ($model->getPostableAmount() > $this->costBasis) {
            throw new InvalidVoucherException(
                "Proceeds ({$model->getPostableAmount()}) exceed costBasis ({$this->costBasis}) - this is a gain, not a loss; use InvestmentSaleGainTemplate instead."
            );
        }
    }
}
