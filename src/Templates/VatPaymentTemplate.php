<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Support\Money;

/**
 * $model->getPostableAmount() is the Output VAT balance being cleared. $inputVatAmount
 * is the Input VAT balance to offset against it, known to the caller
 * beforehand (e.g. read from the ledger by the caller's own code before
 * constructing this template) - this template never queries it. If input
 * exceeds output, that's a refund due, not a payment - see Tax Refund instead.
 */
class VatPaymentTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPresetContract $outputVatAccount = AccountPreset::OUTPUT_VAT,
        protected AccountPresetContract $inputVatAccount = AccountPreset::INPUT_VAT,
        protected AccountPresetContract $cashAccount = AccountPreset::CASH,
        protected string|int|float $inputVatAmount = 0,
    ) {}

    public function configure(): void
    {
        // Output VAT → Input VAT (offset) + Cash (net remittance)
        $this->debit($this->outputVatAccount);

        if (Money::isPositive($this->inputVatAmount)) {
            $this->credit($this->inputVatAccount, fn () => $this->roundAmount($this->inputVatAmount));
        }

        $this->credit($this->cashAccount, fn ($amount) => Money::sub($amount, $this->roundAmount($this->inputVatAmount)));
    }

    public function validate(PostableModel $model): void
    {
        if (Money::isNegative($this->inputVatAmount)) {
            throw new InvalidVoucherException("inputVatAmount must be >= 0, got {$this->inputVatAmount}");
        }

        if (Money::compare($this->inputVatAmount, $model->getPostableAmount()) > 0) {
            throw new InvalidVoucherException(
                "inputVatAmount ({$this->inputVatAmount}) exceeds outputVat ({$model->getPostableAmount()}) - this is a refund due, not a payment."
            );
        }
    }
}
