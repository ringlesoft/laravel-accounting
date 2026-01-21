<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Carbon\Carbon;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;
use RingleSoft\LaravelAccounting\Enums\EntryType;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class VoucherData
{
    public function __construct(
        /**
         * @var VoucherDataItem[]
         */
        public array $entries = [],
        public ?Carbon $date = null,
        public ?string $description = null,
        public ?string $reference = null,
        public ?CurrencyCode $currency = null,
        public array $metadata = [],
    ) {
        $this->date = $this->date ?? now();
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
        $this->entries[] = new VoucherDataItem(EntryType::DEBIT, $accountId, $amount, $memo);
        return $this;
    }

    /**
     * Add a credit entry
     */
    public function credit(int|string $accountId, float $amount, ?string $memo = null): self
    {
        $this->entries[] = new VoucherDataItem(EntryType::CREDIT, $accountId, $amount, $memo);
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
    public function currency(string|CurrencyCode $currencyCode): self
    {
        $this->currency = is_string($currencyCode) ? CurrencyCode::from($currencyCode) : $currencyCode;
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
     * @throws InvalidVoucherException
     */
    public function validate(): void
    {
        if (empty($this->entries)) {
            throw new InvalidVoucherException('Voucher must have at least two entries');
        }

        if (count($this->entries) < 2) {
            throw new InvalidVoucherException('Voucher must have at least two entries (debit and credit)');
        }
        $totalCredit = array_reduce($this->entries, static function ($carry, $entry) {
            return $carry +  ($entry->isCredit() ?  $entry->amount : 0);
        }, 0);
        $totalDebit = array_reduce($this->entries, static function ($carry, $entry) {
            return $carry +  ($entry->isDebit() ?  $entry->amount : 0);
        }, 0);
        $totalAmount = $totalCredit - $totalDebit;
        if (abs($totalAmount) > 0.01) {
            throw new InvalidVoucherException(
                sprintf('Voucher is not balanced. Debit:  %.2f, Credit: %.2f', $totalCredit, $totalDebit)
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
            'currency_code' => $this->currency->value,
            'metadata' => $this->metadata,
            'entries' => array_map(static fn ($entry) => $entry->toArray(), $this->entries),
        ];
    }

    /**
     * Create a new instance
     */
    public static function make(): self
    {
        return new self;
    }
}
