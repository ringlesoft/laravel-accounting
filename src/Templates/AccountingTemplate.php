<?php

namespace RingleSoft\LaravelAccounting\Templates;

use Closure;
use Illuminate\Support\Carbon;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Contracts\PostableTemplate;
use RingleSoft\LaravelAccounting\DTOs\AccountTemplateEntry;
use RingleSoft\LaravelAccounting\DTOs\TaxData;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\DTOs\VoucherDataItem;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Models\Account;

abstract class AccountingTemplate implements PostableTemplate
{
    /**
     * @var AccountTemplateEntry[] $entries List of journal item entries in the template
     */
    protected array $entries = [];

    /**
     * @var TaxData[] $taxes List of tax data in the template
     */
    protected array $taxes = [];

    protected string $description;
    protected ?Carbon $date = null;
    protected array $metadata = [];

    protected function addEntry(AccountTemplateEntry $entry): void
    {
        // TODO: ensure no duplicate entries, or merge them
        $this->entries[] = $entry;
    }

    protected function addTax(TaxData $entry): void
    {
        // TODO: ensure no duplicate entries, or merge them
        $this->taxes[] = $entry;
    }

    public function configure(): void
    {
        // Is this method even needed?
    }

    public function debit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self
    {
        $this->addEntry(AccountTemplateEntry::debit($account, $amount, $memo));
        return $this;
    }

    public function ccredit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self
    {
        $this->addEntry(AccountTemplateEntry::credit($account, $amount, $memo));
        return $this;
    }

    /** USELESS **/
    public function tax(AccountPreset $taxAccount, float $rate, ?Closure $amount, ?AccountPreset $baseAccount): self
    {
        $this->addTax(new TaxData($taxAccount, $rate, $amount, $baseAccount));
        return $this;
    }

    public function make(PostableModel $model): VoucherData
    {
        $amount = $model->getAmount();
        $voucherEntries = [];
        $journalPresets = array_map(fn(AccountPreset $preset) => $preset->value, $this->entries) ?? [];
        $taxPresets = array_map(fn(TaxData $tax) => $tax->account->value, $this->taxes) ?? [];
        $accounts = Account::query()->whereIn('preset', $journalPresets)->get()->pluck('id', 'preset');
        $taxAccounts = Account::query()->whereIn('preset', $taxPresets)->get()->pluck('id', 'preset');
        $accounts->merge($taxAccounts);
        // TODO: if any is missing, throw an exception
        foreach ($this->entries as $entry) {
            $account = $accounts->get($entry->account->value);
            $amount = is_callable($entry->amount) ? ($entry->amount($amount)) : (float)($amount ?? 0);
            $voucherEntries[] = new VoucherDataItem($account, $amount, $entry->memo);
        }
        return new VoucherData($voucherEntries, $this->date, $this->description, null, $this->metadata);
    }
}
