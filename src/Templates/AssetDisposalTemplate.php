<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * Retiring/scrapping an asset with no sale at all - the entire remaining
 * book value is a loss, unconditionally. $model->getPostableAmount() is the
 * asset's original cost; $accumulatedDepreciation is its own figure as of
 * disposal, known to the caller beforehand. For a disposal with proceeds,
 * use AssetSaleGainTemplate/AssetSaleLossTemplate instead.
 */
class AssetDisposalTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $accumulatedDepreciationAccount = AccountPreset::ACCUMULATED_DEPRECIATION,
        protected AccountPresetContract $lossAccount = AccountPreset::LOSS_ON_SALE_OF_ASSETS,
        protected AccountPresetContract $fixedAssetAccount = AccountPreset::FIXED_ASSETS,
        protected string|int|float $accumulatedDepreciation = 0,
    ) {}

    public function configure(): void
    {
        // Accumulated Depreciation + Loss on Disposal → Fixed Assets (at cost)
        $this->debit($this->accumulatedDepreciationAccount, fn () => $this->accumulatedDepreciation);
        $this->debit($this->lossAccount, fn ($amount) => Money::sub($amount, $this->accumulatedDepreciation));
        $this->credit($this->fixedAssetAccount);
    }

    public function validate(PostableModel $model): void
    {
        if (Money::isNegative($this->accumulatedDepreciation) || Money::compare($this->accumulatedDepreciation, $model->getPostableAmount()) > 0) {
            throw new InvalidVoucherException(
                "accumulatedDepreciation must be between 0 and the asset's original cost ({$model->getPostableAmount()}), got {$this->accumulatedDepreciation}"
            );
        }
    }
}
