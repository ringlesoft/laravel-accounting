<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Enums\EntryType;

class VoucherDataItem
{
    public function __construct(
        public EntryType $type,
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

        if ($this->amount <= 0.0) {
            throw new InvalidArgumentException('Amount cannot be zero or less');
        }
    }

    /**
     * Check if this is a debit entry
     */
    public function isDebit(): bool
    {
        return $this->type === EntryType::DEBIT;
    }

    /**
     * Check if this is a credit entry
     */
    public function isCredit(): bool
    {
        return $this->type === EntryType::CREDIT;
    }

    /**
     * Get the debit amount
     */
    public function getDebitAmount(): float
    {
        return $this->isDebit() ? $this->amount : 0;
    }

    /**
     * Get the credit amount
     */
    public function getCreditAmount(): float
    {
        return $this->isCredit() ? $this->amount : 0;
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
        return new self(EntryType::DEBIT, $accountId, abs($amount), $memo);
    }

    /**
     * Create a credit entry
     */
    public static function credit(int|string $accountId, float $amount, ?string $memo = null): self
    {
        return new self(EntryType::CREDIT, $accountId, -abs($amount), $memo);
    }
}
