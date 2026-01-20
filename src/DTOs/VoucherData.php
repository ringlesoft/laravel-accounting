<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Carbon\Carbon;
use InvalidArgumentException;

class VoucherData
{
    public ?string $currency_code = null;

    public function __construct(
        public array $entries = [],
        public ?Carbon $date = null,
        public ?string $description = null,
        public ?string $reference = null,
        public array $metadata = []
    ) {
        $this->date = $this->date ?? now();
        $this->validate();
    }

    /**
     * Set the transaction date
     */
    public function on(Carbon $date): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Set the description
     */
    public function describe(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Add a debit entry
     */
    public function debit(int|string $accountId, float $amount, ?string $memo = null): self
    {
        $this->entries[] = new VoucherDataItem($accountId, abs($amount), $memo);
        return $this;
    }

    /**
     * Add a credit entry
     */
    public function credit(int|string $accountId, float $amount, ?string $memo = null): self
    {
        $this->entries[] = new VoucherDataItem($accountId, -abs($amount), $memo);
        return $this;
    }

    /**
     * Set the reference
     */
    public function reference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * Set currency code
     */
    public function currency(string $currencyCode): self
    {
        $this->currency_code = $currencyCode;
        return $this;
    }

    /**
     * Add metadata
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Validate the voucher data
     */
    public function validate(): void
    {
        if (empty($this->entries)) {
            return;
        }

        if (count($this->entries) < 2) {
            throw new InvalidArgumentException('Voucher must have at least two entries (debit and credit)');
        }

        $totalAmount = array_reduce($this->entries, function ($carry, $entry) {
            return $carry + $entry->amount;
        }, 0);

        if (abs($totalAmount) > 0.01) {
            throw new InvalidArgumentException(
                sprintf('Voucher is not balanced. Total amount: %.2f', $totalAmount)
            );
        }
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date?->toDateString(),
            'description' => $this->description,
            'reference' => $this->reference,
            'currency_code' => $this->currency_code,
            'metadata' => $this->metadata,
            'entries' => array_map(fn($entry) => $entry->toArray(), $this->entries),
        ];
    }

    /**
     * Create a new instance
     */
    public static function make(): self
    {
        return new self();
    }
}
