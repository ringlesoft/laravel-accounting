<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

/**
 * Retiring/scrapping an asset with no sale at all - the entire remaining
 * book value is a loss, unconditionally. $model->getAmount() is the
 * asset's original cost; $accumulatedDepreciation is its own figure as of
 * disposal, known to the caller beforehand. For a disposal with proceeds,
 * use AssetSaleGainTemplate/AssetSaleLossTemplate instead.
 */
class AssetDisposalTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $accumulatedDepreciationAccount = AccountPreset::ACCUMULATED_DEPRECIATION,
        protected AccountPreset $lossAccount = AccountPreset::LOSS_ON_SALE_OF_ASSETS,
        protected AccountPreset $fixedAssetAccount = AccountPreset::FIXED_ASSETS,
        protected float $accumulatedDepreciation = 0.0,
    ) {}

    public function configure(): void
    {
        // Accumulated Depreciation + Loss on Disposal → Fixed Assets (at cost)
        $this->debit($this->accumulatedDepreciationAccount, fn () => $this->accumulatedDepreciation);
        $this->debit($this->lossAccount, fn ($amount) => $amount - $this->accumulatedDepreciation);
        $this->credit($this->fixedAssetAccount);
    }

    public function validate(PostableModel $model): void
    {
        if ($this->accumulatedDepreciation < 0 || $this->accumulatedDepreciation > $model->getAmount()) {
            throw new InvalidVoucherException(
                "accumulatedDepreciation must be between 0 and the asset's original cost ({$model->getAmount()}), got {$this->accumulatedDepreciation}"
            );
        }
    }
}
