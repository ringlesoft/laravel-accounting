<?php

namespace RingleSoft\LaravelAccounting\Templates;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Contracts\PostableTemplate;
use RingleSoft\LaravelAccounting\Contracts\ResolvesAccountPresets;
use RingleSoft\LaravelAccounting\DTOs\AccountTemplateEntry;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\DTOs\VoucherDataItem;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;
use RingleSoft\LaravelAccounting\Exceptions\AccountNotFoundException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
use RingleSoft\LaravelAccounting\Exceptions\ParentAccountPostingException;
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\Account as AccountAlias;
use RingleSoft\LaravelAccounting\Support\Money;

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
     * @var Closure(PostableModel): CurrencyCode|CurrencyCode
     */
    protected Closure|CurrencyCode $currency = CurrencyCode::USD;

    /**
     * @var array<string, int|string> Preset value => explicit account id/UUID
     */
    protected array $accountOverrides = [];

    /**
     * The voucher currency, resolved by make() before any amount is computed
     */
    protected ?CurrencyCode $resolvedCurrency = null;

    protected function addEntry(AccountTemplateEntry $entry): void
    {
        // TODO: ensure no duplicate entries, or merge them
        $this->entries[] = $entry;
    }

    /**
     * @param  Closure(string): (string|int|float)|null  $amount  receives the postable amount as a Money::SCALE numeric string
     * @return $this
     */
    public function debit(AccountPresetContract $account, ?Closure $amount = null, Closure|string|null $memo = null): self
    {
        $this->addEntry(AccountTemplateEntry::debit($account, $amount, $memo));

        return $this;
    }

    /**
     * @param  Closure(string): (string|int|float)|null  $amount  receives the postable amount as a Money::SCALE numeric string
     * @return $this
     */
    public function credit(AccountPresetContract $account, ?Closure $amount = null, Closure|string|null $memo = null): self
    {
        $this->addEntry(AccountTemplateEntry::credit($account, $amount, $memo));

        return $this;
    }

    /**
     * Set the description of the voucher
     *
     * @param  Closure(PostableModel $model): string|string  $description
     */
    public function describe(Closure|string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the date of the voucher
     *
     * @param  Closure(PostableModel $model): Carbon|Carbon  $date
     */
    public function date(Closure|Carbon $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Explicitly resolve a preset to a specific account, bypassing the
     * default preset lookup for that entry. Use this to pick one child
     * account under an ambiguous parent/category preset (e.g.
     * COST_OF_GOODS_SOLD with several children), or to target a fully
     * custom account that carries no preset at all.
     *
     * @param  AccountAlias|int|string  $account  An Account instance, or its id/UUID
     * @return $this
     */
    public function resolveAccount(AccountPresetContract $preset, AccountAlias|int|string $account): self
    {
        $this->accountOverrides[$preset->value] = $this->normalizeAccountOverride($preset, $account);

        return $this;
    }

    /**
     * Resolve an override value (an Account, id, or UUID) to the id to post
     * to, warning if it isn't a child of the preset's own account.
     */
    protected function normalizeAccountOverride(AccountPresetContract $preset, AccountAlias|int|string $account): int|string
    {
        $accountModel = $account instanceof AccountAlias ? $account : AccountAlias::find($account);

        if ($accountModel) {
            $this->warnIfNotChildOfPreset($preset, $accountModel);

            return $accountModel->getKey();
        }

        return $account;
    }

    /**
     * Log a warning when the account given resolveAccount() isn't actually a child of the preset's account -
     * most likely a typo or a preset/account mismatch, but still allowed
     * since the developer may have a deliberate reason (e.g. a flatter COA).
     */
    protected function warnIfNotChildOfPreset(AccountPresetContract $preset, AccountAlias $account): void
    {
        $presetAccount = AccountAlias::where('preset', $preset->value)->first();

        if (! $presetAccount || $account->getKey() === $presetAccount->getKey()) {
            return;
        }

        if ($account->parent_id !== $presetAccount->getKey()) {
            Log::warning(sprintf(
                "resolveAccount(): account '%s' (code %s) is not a child of preset %s's account '%s' (code %s). Proceeding anyway.",
                $account->name,
                $account->code,
                $preset->value,
                $presetAccount->name,
                $presetAccount->code
            ));
        }
    }

    /**
     * De-duplicate entries by account and type
     *
     * @return $this
     */
    public function deDuplicate(): self
    {
        $deduplicated = [];
        foreach ($this->entries as $entry) {
            $key = $entry->account->value.'_'.$entry->type->value;
            if (isset($deduplicated[$key])) {
                $existingAmount = $deduplicated[$key]->getAmount(0);
                $newAmount = $entry->getAmount(0);
                $deduplicated[$key]->amount = fn ($total) => Money::add($existingAmount, $newAmount);
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
        $instance = new static;
        $instance->configure();
        $entries = $instance->getEntries();
        $accounts = ['DEBIT' => [], 'CREDIT' => []];
        foreach ($entries as $entry) {
            if (! isset($accounts[$entry->type->value][$entry->account->value])) {
                $accounts[$entry->type->value][$entry->account->value] = [];
            }
            $accounts[$entry->type->value][$entry->account->value] = array_merge($accounts[$entry->type->value][$entry->account->value], (LaravelAccounting::accounts()->getLastChildrenByPreset($entry->account)->toArray() ?? []));
        }

        return $accounts;
    }

    /**
     * @throws AccountNotFoundException|InvalidVoucherException|JournalNotBalancedException
     */
    public function make(PostableModel $model): VoucherData
    {
        $this->resolvedCurrency = $this->currency instanceof Closure ? ($this->currency)($model) : $this->currency;
        $amount = $this->roundAmount($model->getPostableAmount());
        $this->configure(); // Configure the template first
        $this->validate($model); // Then let subclasses reject a bad model/parameter combo early

        $voucherEntries = [];
        $journalPresets = array_map(static fn (AccountTemplateEntry $entry) => $entry->account->value, $this->entries);
        $presetAccounts = AccountAlias::whereIn('preset', $journalPresets)->with('children')->get()->keyBy('preset');
        foreach ($this->entries as $entry) {
            $accountId = $this->resolveEntryAccountId($entry->account, $presetAccounts, $model);
            $entryAmount = $this->roundAmount($entry->getAmount($amount));
            if (Money::isZero($entryAmount)) {
                continue; // e.g. a tax line that rounds to nothing in a 0-decimal currency
            }
            $voucherEntries[] = new VoucherDataItem($entry->type, $accountId, $entryAmount, $entry->memo);
        }
        $date = $this->date instanceof Closure ? ($this->date)($model) : ($this->date ?? now());
        $description = $this->description instanceof Closure ? ($this->description)($model) : $this->description;
        $metadata = $this->metadata instanceof Closure ? ($this->metadata)($model) : $this->metadata;
        $reference = $this->reference instanceof Closure ? ($this->reference)($model) : $this->reference;
        $voucher = new VoucherData($voucherEntries, $date, $description, $reference, $this->resolvedCurrency, $metadata);
        $voucher->setSource($model);
        $voucher->validate();

        return $voucher;
    }

    /**
     * Resolve a template entry's preset to the id of the account to actually post to, in priority order:
     * 1. An explicit resolveAccount() override on this template instance;
     * 2. The model's own resolveAccountForPreset(), if it implements ResolvesAccountPresets and returns a non-null answer;
     * 3. the default preset lookup - a preset with no children (a real postable account) or exactly one child (unambiguous)
     *
     * @param  Collection<string, AccountAlias>  $presetAccounts  Keyed by preset value
     *
     * @throws AccountNotFoundException|ParentAccountPostingException
     */
    protected function resolveEntryAccountId(AccountPresetContract $preset, Collection $presetAccounts, PostableModel $model): int|string
    {
        if (array_key_exists($preset->value, $this->accountOverrides)) {
            return $this->accountOverrides[$preset->value];
        }

        if ($model instanceof ResolvesAccountPresets) {
            $resolved = $model->resolveAccountForPreset($preset);
            if ($resolved !== null) {
                return $this->normalizeAccountOverride($preset, $resolved);
            }
        }

        $account = $presetAccounts->get($preset->value);
        if (! $account) {
            throw new AccountNotFoundException('Account not found for preset: '.$preset->value);
        }

        if ($account->children->isEmpty()) {
            return $account->getKey();
        }

        if ($account->children->count() === 1) {
            return $account->children->first()->getKey();
        }

        if (config('laravel_accounting.allow_posting_on_parents', false)) {
            return $account->getKey();
        }

        throw new ParentAccountPostingException(
            "Preset {$preset->value} resolves to '{$account->name}' (code {$account->code}), which has ".
            "{$account->children->count()} child accounts, so it's ambiguous which one to post to. Call ".
            '->resolveAccount('.class_basename($preset)."::{$preset->name}, \$account) on the template to pick one, override ".
            'the relevant constructor parameter with a more specific preset, or inspect '.
            static::class.'::getPostableAccounts() to see the available options.'
        );
    }

    /**
     * Round an amount to the decimals the voucher's currency is posted at.
     * Amount closures that split one amount into several lines round each
     * computed line with this and take the last as the remainder, so the
     * lines still add up after posting rounds them.
     */
    protected function roundAmount(string|int|float $amount): string
    {
        return Money::forCurrency($amount, $this->resolvedCurrency);
    }

    /**
     * Get the entries that will be posted
     *
     * @return AccountTemplateEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Validate the template's own business rules against the model, after
     * configure() has run but before entries are resolved to real accounts.
     * A no-op by default - override to reject an invalid model/parameter
     * combination (e.g. a rate outside 0-1) without needing to override
     * make() itself.
     *
     * @throws InvalidVoucherException
     */
    public function validate(PostableModel $model): void
    {
        // Intentionally empty - override in a subclass to add checks.
    }
}
