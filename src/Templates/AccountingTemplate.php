<?php

namespace RingleSoft\LaravelAccounting\Templates;

use Closure;
use Illuminate\Support\Carbon;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Contracts\PostableTemplate;
use RingleSoft\LaravelAccounting\DTOs\AccountTemplateEntry;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\DTOs\VoucherDataItem;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;
use RingleSoft\LaravelAccounting\Exceptions\AccountNotFoundException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\Account as AccountAlias;

/**
 * @mixin PostableTemplate
 */
abstract class AccountingTemplate implements PostableTemplate
{
    /**
     * @var AccountTemplateEntry[] List of journal item entries in the template
     */
    protected array $entries = [];

    /**
     * @var Closure(PostableModel): string|string|null
     */
    protected Closure|string|null $description = null;

    /**
     * @var Closure(PostableModel): Carbon|Carbon|null
     */
    protected Closure|Carbon|null $date = null;

    /**
     * @var Closure(PostableModel): array|array
     */
    protected Closure|array $metadata = [];

    /**
     * @var Closure(PostableModel): string|string|null
     */
    protected Closure|string|null $reference = null;

    /**
     * @var Closure(PostableModel): CurrencyCode|CurrencyCOde
     */
    protected Closure|CurrencyCode $currency = CurrencyCode::USD;

    protected function addEntry(AccountTemplateEntry $entry): void
    {
        // TODO: ensure no duplicate entries, or merge them
        $this->entries[] = $entry;
    }

    /**
     * @param Closure(float): float|null $amount
     * @return $this
     */
    public function debit(AccountPreset $account, ?Closure $amount = null, Closure|string|null $memo = null): self
    {
        $this->addEntry(AccountTemplateEntry::debit($account, $amount, $memo));

        return $this;
    }

    /**
     * @param Closure(float): float|null $amount
     * @return $this
     */
    public function credit(AccountPreset $account, ?Closure $amount = null, Closure|string|null $memo = null): self
    {
        $this->addEntry(AccountTemplateEntry::credit($account, $amount, $memo));

        return $this;
    }

    /**
     * Set the description of the voucher
     *
     * @param Closure(PostableModel $model): string|string $description
     */
    public function describe(Closure|string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the date of the voucher
     *
     * @param Closure(PostableModel $model): Carbon|Carbon $date
     */
    public function date(Closure|Carbon $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * De-duplicate entries by account and type
     * @return $this
     */
    public function deDuplicate(): self
    {
        $deduplicated = [];
        foreach ($this->entries as $entry) {
            $key = $entry->account->value . '_' . $entry->type->value;
            if (isset($deduplicated[$key])) {
                $existingAmount = $deduplicated[$key]->getAmount(0);
                $newAmount = $entry->getAmount(0);
                $deduplicated[$key]->amount = fn($total) => $existingAmount + $newAmount;
            } else {
                $deduplicated[$key] = $entry;
            }
        }
        $this->entries = array_values($deduplicated);
        return $this;
    }

    /**
     * @return AccountTemplateEntry[]
     */
    public static function getPostableAccounts(): array
    {
        $instance = new static();
        $instance->configure();
        $entries = $instance->getEntries();
        $accounts = ['DEBIT' => [], 'CREDIT' => []];
        foreach ($entries as $entry) {
            if(!isset($accounts[$entry->type->value][$entry->account->value])){
                $accounts[$entry->type->value][$entry->account->value] = [];
            }
            $accounts[$entry->type->value][$entry->account->value] = array_merge($accounts[$entry->type->value][$entry->account->value] , (LaravelAccounting::accounts()->getLastChildrenByPreset($entry->account)->toArray() ?? []));
        }
        return $accounts;
    }


    /**
     * @throws AccountNotFoundException|InvalidVoucherException|JournalNotBalancedException
     */
    public function make(PostableModel $model): VoucherData
    {
        $amount = $model->getAmount();
        $this->configure(); // Configure the template first

        $voucherEntries = [];
        $journalPresets = array_map(static fn(AccountTemplateEntry $entry) => $entry->account->value, $this->entries);
        $accounts = AccountAlias::whereIn('preset', $journalPresets)->get()->pluck('id', 'preset');
        foreach ($this->entries as $entry) {
            $accountId = $accounts->get($entry->account->value);
            if (!$accountId) {
                throw new AccountNotFoundException('Account not found for preset: ' . $entry->account->value);
            }
            $entryAmount = $entry->getAmount($amount);
            $voucherEntries[] = new VoucherDataItem($entry->type, $accountId, $entryAmount, $entry->memo);
        }
        $date = $this->date instanceof Closure ? ($this->date)($model) : ($this->date ?? now());
        $description = $this->description instanceof Closure ? ($this->description)($model) : $this->description;
        $metadata = $this->metadata instanceof Closure ? ($this->metadata)($model) : $this->metadata;
        $currency = $this->currency instanceof Closure ? ($this->currency)($model) : $this->currency;
        $reference = $this->reference instanceof Closure ? ($this->reference)($model) : $this->reference;
        $voucher = new VoucherData($voucherEntries, $date, $description, $reference, $currency, $metadata);
        $voucher->setSource($model);
        $voucher->validate();

        return $voucher;
    }

    /**
     * Get the entries that will be posted
     * @return AccountTemplateEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
