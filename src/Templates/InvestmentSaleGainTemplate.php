<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * $model->getPostableAmount() is the cash proceeds received. $costBasis is the
 * investment's own cost, known to the caller beforehand - this template
 * never queries it. Covers only the case where proceeds exceed cost (a
 * gain); use InvestmentSaleLossTemplate for a loss.
 */
class InvestmentSaleGainTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $investmentAccount = AccountPreset::SHORT_TERM_INVESTMENTS,
        protected AccountPresetContract $gainAccount = AccountPreset::GAIN_ON_SALE_OF_ASSETS,
        protected string|int|float $costBasis = 0,
    ) {}

    public function configure(): void
    {
        // Cash (proceeds) → Investment (at cost) + Gain on Sale
        $this->debit($this->cashAccount);
        $this->credit($this->investmentAccount, fn () => $this->roundAmount($this->costBasis));
        $this->credit($this->gainAccount, fn ($amount) => Money::sub($amount, $this->roundAmount($this->costBasis)));
    }

    public function validate(PostableModel $model): void
    {
        if (Money::isNegative($this->costBasis)) {
            throw new InvalidVoucherException("costBasis must be >= 0, got {$this->costBasis}");
        }

        if (Money::compare($model->getPostableAmount(), $this->costBasis) < 0) {
            throw new InvalidVoucherException(
                "Proceeds ({$model->getPostableAmount()}) are below costBasis ({$this->costBasis}) - this is a loss, not a gain; use InvestmentSaleLossTemplate instead."
            );
        }
    }
}
