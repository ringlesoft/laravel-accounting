<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

/**
 * $model->getPostableAmount() is the cash proceeds received. $originalCost and
 * $accumulatedDepreciation are the asset's own figures as of disposal,
 * known to the caller beforehand - this template never queries them.
 * Covers only the case where proceeds exceed book value (a gain); use
 * AssetSaleLossTemplate for a loss.
 */
class AssetSaleGainTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $accumulatedDepreciationAccount = AccountPreset::ACCUMULATED_DEPRECIATION,
        protected AccountPresetContract $fixedAssetAccount = AccountPreset::FIXED_ASSETS,
        protected AccountPresetContract $gainAccount = AccountPreset::GAIN_ON_SALE_OF_ASSETS,
        protected float $originalCost = 0.0,
        protected float $accumulatedDepreciation = 0.0,
    ) {}

    public function configure(): void
    {
        // Cash (proceeds) + Accumulated Depreciation → Fixed Assets (at cost) + Gain on Sale
        $this->debit($this->cashAccount);
        $this->debit($this->accumulatedDepreciationAccount, fn () => $this->accumulatedDepreciation);
        $this->credit($this->fixedAssetAccount, fn () => $this->originalCost);
        $this->credit(
            $this->gainAccount,
            fn ($amount) => $amount + $this->accumulatedDepreciation - $this->originalCost
        );
    }

    public function validate(PostableModel $model): void
    {
        if ($this->originalCost < 0) {
            throw new InvalidVoucherException("originalCost must be >= 0, got {$this->originalCost}");
        }

        if ($this->accumulatedDepreciation < 0 || $this->accumulatedDepreciation > $this->originalCost) {
            throw new InvalidVoucherException(
                "accumulatedDepreciation must be between 0 and originalCost ({$this->originalCost}), got {$this->accumulatedDepreciation}"
            );
        }

        $bookValue = $this->originalCost - $this->accumulatedDepreciation;

        if ($model->getPostableAmount() < $bookValue) {
            throw new InvalidVoucherException(
                "Proceeds ({$model->getPostableAmount()}) are below book value ({$bookValue}) - this is a loss, not a gain; use AssetSaleLossTemplate instead."
            );
        }
    }
}
