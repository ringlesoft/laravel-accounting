<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

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
        protected float $inputVatAmount = 0.0,
    ) {}

    public function configure(): void
    {
        // Output VAT → Input VAT (offset) + Cash (net remittance)
        $this->debit($this->outputVatAccount);

        if ($this->inputVatAmount > 0) {
            $this->credit($this->inputVatAccount, fn () => $this->inputVatAmount);
        }

        $this->credit($this->cashAccount, fn ($amount) => $amount - $this->inputVatAmount);
    }

    public function validate(PostableModel $model): void
    {
        if ($this->inputVatAmount < 0) {
            throw new InvalidVoucherException("inputVatAmount must be >= 0, got {$this->inputVatAmount}");
        }

        if ($this->inputVatAmount > $model->getPostableAmount()) {
            throw new InvalidVoucherException(
                "inputVatAmount ({$this->inputVatAmount}) exceeds outputVat ({$model->getPostableAmount()}) - this is a refund due, not a payment."
            );
        }
    }
}
