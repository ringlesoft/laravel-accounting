<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use InvalidArgumentException;

class VoucherDataItem
{
    public function __construct(
        public int|string $accountId,
        public float $amount,
        public ?string $memo = null,
        public ?array $dimension = null
    ) {
        $this->validate();
    }

    /**
     * Validate the voucher item
     */
    public function validate(): void
    {
        if (empty($this->accountId)) {
            throw new InvalidArgumentException('Account ID is required');
        }

        if ($this->amount === 0.0) {
            throw new InvalidArgumentException('Amount cannot be zero');
        }
    }

    /**
     * Check if this is a debit entry
     */
    public function isDebit(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if this is a credit entry
     */
    public function isCredit(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get the debit amount
     */
    public function getDebitAmount(): float
    {
        return $this->amount > 0 ? abs($this->amount) : 0;
    }

    /**
     * Get the credit amount
     */
    public function getCreditAmount(): float
    {
        return $this->amount < 0 ? abs($this->amount) : 0;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'amount' => $this->amount,
            'debit_amount' => $this->getDebitAmount(),
            'credit_amount' => $this->getCreditAmount(),
            'memo' => $this->memo,
            'dimension' => $this->dimension,
        ];
    }

    /**
     * Create a debit entry
     */
    public static function debit(int|string $accountId, float $amount, ?string $memo = null): self
    {
        return new self($accountId, abs($amount), $memo);
    }

    /**
     * Create a credit entry
     */
    public static function credit(int|string $accountId, float $amount, ?string $memo = null): self
    {
        return new self($accountId, -abs($amount), $memo);
    }
}
