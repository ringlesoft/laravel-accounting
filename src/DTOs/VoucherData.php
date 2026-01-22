<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;
use RingleSoft\LaravelAccounting\Enums\EntryType;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Services\JournalService;
use Throwable;

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
        public Model|PostableModel|null $sourceModel = null,
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
     * @return $this
     */
    public function setSource(Model|PostableModel $modal): self
    {
        $this->sourceModel = $modal;

        return $this;
    }

    /**
     * Validate the voucher data
     *
     * @throws InvalidVoucherException
     * @throws JournalNotBalancedException
     */
    public function validate(): void
    {
        (app(JournalService::class))->validateVoucher($this);
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

    /**
     * Create instance for a PostableModal
     */
    public static function for(Model|PostableModel $sourceModel): self
    {
        $voucher = new self;
        $voucher->sourceModel = $sourceModel;

        return $voucher;
    }

    /**
     * Create a journal entry from the voucher data
     *
     * @throws Throwable
     */
    public function createJournal(): JournalEntry
    {
        return app(JournalService::class)->createDraft($this);
    }
}
