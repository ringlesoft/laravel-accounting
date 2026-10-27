<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Enums\EntryType;
use RingleSoft\LaravelAccounting\Support\Money;

class VoucherDataItem
{
    /**
     * @var string The amount as a numeric string with Money::SCALE decimal places
     */
    public string $amount;

    public function __construct(
        public EntryType $type,
        public int|string $accountId,
        string|int|float $amount,
        public ?string $memo = null,
        public ?array $dimension = null
    ) {
        $this->amount = Money::of($amount);
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

        if (! Money::isPositive($this->amount)) {
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
    public function getDebitAmount(): string
    {
        return $this->isDebit() ? $this->amount : Money::ZERO;
    }

    /**
     * Get the credit amount
     */
    public function getCreditAmount(): string
    {
        return $this->isCredit() ? $this->amount : Money::ZERO;
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
    public static function debit(int|string $accountId, string|int|float $amount, ?string $memo = null): self
    {
        return new self(EntryType::DEBIT, $accountId, Money::abs($amount), $memo);
    }

    /**
     * Create a credit entry
     */
    public static function credit(int|string $accountId, string|int|float $amount, ?string $memo = null): self
    {
        return new self(EntryType::CREDIT, $accountId, Money::abs($amount), $memo);
    }
}
