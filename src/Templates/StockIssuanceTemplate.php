<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

class StockIssuanceTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected AccountPresetContract $commonStockAccount = AccountPreset::COMMON_STOCK,
        protected AccountPresetContract $additionalPaidInCapitalAccount = AccountPreset::ADDITIONAL_PAID_IN_CAPITAL,
        protected string|int|float $parValuePerShare = 0,
        protected string|int|float $numberOfShares = 0,
    ) {}

    public function configure(): void
    {
        // Cash → Common Stock (par value) + Additional Paid-in Capital (excess over par)
        $parValue = Money::mul($this->parValuePerShare, $this->numberOfShares);
        $hasParValue = Money::isPositive($this->parValuePerShare);

        $this->debit($this->cashAccount);
        $this->credit($this->commonStockAccount, fn ($amount) => $hasParValue ? $parValue : $amount);

        if ($hasParValue) {
            $this->credit($this->additionalPaidInCapitalAccount, fn ($amount) => Money::sub($amount, $parValue));
        }
    }

    public function validate(PostableModel $model): void
    {
        if (Money::isNegative($this->parValuePerShare)) {
            throw new InvalidVoucherException("parValuePerShare must be >= 0, got {$this->parValuePerShare}");
        }

        if (Money::isNegative($this->numberOfShares)) {
            throw new InvalidVoucherException("numberOfShares must be >= 0, got {$this->numberOfShares}");
        }

        $parValue = Money::mul($this->parValuePerShare, $this->numberOfShares);

        if (Money::compare($parValue, $model->getPostableAmount()) > 0) {
            throw new InvalidVoucherException(
                "Total par value ({$parValue}) cannot exceed the total proceeds ({$model->getPostableAmount()})"
            );
        }
    }
}
