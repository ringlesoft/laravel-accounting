<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class StockIssuanceTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $commonStockAccount = AccountPreset::COMMON_STOCK,
        protected AccountPreset $additionalPaidInCapitalAccount = AccountPreset::ADDITIONAL_PAID_IN_CAPITAL,
        protected float $parValuePerShare = 0.0,
        protected float $numberOfShares = 0.0,
    ) {}

    public function configure(): void
    {
        // Cash → Common Stock (par value) + Additional Paid-in Capital (excess over par)
        $parValue = $this->parValuePerShare * $this->numberOfShares;

        $this->debit($this->cashAccount);
        $this->credit($this->commonStockAccount, fn ($amount) => $this->parValuePerShare > 0 ? $parValue : $amount);

        if ($this->parValuePerShare > 0) {
            $this->credit($this->additionalPaidInCapitalAccount, fn ($amount) => $amount - $parValue);
        }
    }

    public function validate(PostableModel $model): void
    {
        if ($this->parValuePerShare < 0) {
            throw new InvalidVoucherException("parValuePerShare must be >= 0, got {$this->parValuePerShare}");
        }

        if ($this->numberOfShares < 0) {
            throw new InvalidVoucherException("numberOfShares must be >= 0, got {$this->numberOfShares}");
        }

        $parValue = $this->parValuePerShare * $this->numberOfShares;

        if ($parValue > $model->getPostableAmount()) {
            throw new InvalidVoucherException(
                "Total par value ({$parValue}) cannot exceed the total proceeds ({$model->getPostableAmount()})"
            );
        }
    }
}
