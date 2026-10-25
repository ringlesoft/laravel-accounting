<?php

use Database\Seeders\AccountTypeSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Contracts\ResolvesAccountPresets;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Enums\EntryType;
use RingleSoft\LaravelAccounting\Enums\TransactionTemplate;
use RingleSoft\LaravelAccounting\Exceptions\ParentAccountPostingException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Services\AccountService;
use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;
use RingleSoft\LaravelAccounting\Templates\CashSaleTemplate;
use RingleSoft\LaravelAccounting\Traits\Postable;

uses(RefreshDatabase::class);

enum CustomPreset: string implements AccountPresetContract
{
    case SECONDARY_BANK = 'SECONDARY_BANK';
    case CONSULTING_REVENUE = 'CONSULTING_REVENUE';
}

class SaleModel extends Model implements PostableModel
{
    use Postable;

    public function getPostableAmount(): float
    {
        return 100.0;
    }
}

class SaleModelResolvingPresets extends SaleModel implements ResolvesAccountPresets
{
    public static ?AccountPresetContract $received = null;

    public static int|string|null $resolveTo = null;

    public function resolveAccountForPreset(AccountPresetContract $preset): Account|int|string|null
    {
        if ($preset === CustomPreset::SECONDARY_BANK) {
            static::$received = $preset;

            return static::$resolveTo;
        }

        return null;
    }
}

class ConsultingTemplate extends AccountingTemplate
{
    public function configure(): void
    {
        $this->debit(CustomPreset::SECONDARY_BANK)
            ->credit(CustomPreset::CONSULTING_REVENUE);
    }
}

beforeEach(function () {
    // These tests build small standalone charts; the chart structure rules
    // are covered by ChartOfAccountsTest.
    config()->set('laravel_accounting.enforce_chart_structure', false);
    require_once __DIR__.'/../database/seeders/AccountTypeSeeder.php';
    (new AccountTypeSeeder)->run();
});

function presetAccount(string $code, AccountTypeEnum $type, ?AccountPresetContract $preset, ?Account $parent = null): Account
{
    return app(AccountService::class)->create(array_filter([
        'code' => $code,
        'name' => 'Account '.$code,
        'account_type_id' => $type->getId(),
        'preset' => $preset?->value,
        'parent_id' => $parent?->getKey(),
    ]));
}

/**
 * @return array<string, float> account id => signed amount (debit +, credit -)
 */
function voucherLines(AccountingTemplate $template, PostableModel $model): array
{
    $lines = [];
    foreach ($template->make($model)->entries as $item) {
        $lines[$item->accountId] = $item->type === EntryType::DEBIT ? $item->amount : -$item->amount;
    }

    return $lines;
}

it('posts a custom template built entirely from custom presets', function () {
    $bank = presetAccount('1000', AccountTypeEnum::ASSET, CustomPreset::SECONDARY_BANK);
    $revenue = presetAccount('4000', AccountTypeEnum::INCOME, CustomPreset::CONSULTING_REVENUE);

    expect(voucherLines(new ConsultingTemplate, new SaleModel))
        ->toBe([$bank->id => 100.0, $revenue->id => -100.0]);
});

it('accepts custom presets in built-in template constructors alongside built-in ones', function () {
    $bank = presetAccount('1000', AccountTypeEnum::ASSET, CustomPreset::SECONDARY_BANK);
    $sales = presetAccount('4000', AccountTypeEnum::INCOME, AccountPreset::SALES_REVENUE);

    $template = new CashSaleTemplate(cashAccount: CustomPreset::SECONDARY_BANK, taxAccount: null);

    expect(voucherLines($template, new SaleModel))
        ->toBe([$bank->id => 100.0, $sales->id => -100.0]);
});

it('lets resolveAccount() override a custom preset', function () {
    presetAccount('1000', AccountTypeEnum::ASSET, CustomPreset::SECONDARY_BANK);
    $other = presetAccount('1001', AccountTypeEnum::ASSET, null);
    $revenue = presetAccount('4000', AccountTypeEnum::INCOME, CustomPreset::CONSULTING_REVENUE);

    $template = (new ConsultingTemplate)->resolveAccount(CustomPreset::SECONDARY_BANK, $other);

    expect(voucherLines($template, new SaleModel))
        ->toBe([$other->id => 100.0, $revenue->id => -100.0]);
});

it('passes custom presets to a model implementing ResolvesAccountPresets', function () {
    presetAccount('1000', AccountTypeEnum::ASSET, CustomPreset::SECONDARY_BANK);
    $other = presetAccount('1001', AccountTypeEnum::ASSET, null);
    $revenue = presetAccount('4000', AccountTypeEnum::INCOME, CustomPreset::CONSULTING_REVENUE);
    SaleModelResolvingPresets::$resolveTo = $other->id;

    expect(voucherLines(new ConsultingTemplate, new SaleModelResolvingPresets))
        ->toBe([$other->id => 100.0, $revenue->id => -100.0])
        ->and(SaleModelResolvingPresets::$received)->toBe(CustomPreset::SECONDARY_BANK);
});

it('looks up custom presets through AccountService', function () {
    $parent = presetAccount('1000', AccountTypeEnum::ASSET, CustomPreset::SECONDARY_BANK);
    $child = presetAccount('1001', AccountTypeEnum::ASSET, null, $parent);
    $service = app(AccountService::class);

    expect($service->findByPreset(CustomPreset::SECONDARY_BANK)->id)->toBe($parent->id)
        ->and($service->getLastChildrenByPreset(CustomPreset::SECONDARY_BANK)->pluck('id')->all())->toBe([$child->id])
        ->and($service->getAccountsForPresetGroup(CustomPreset::SECONDARY_BANK)->pluck('id')->all())->toBe([$child->id])
        ->and($service->findByPreset(CustomPreset::CONSULTING_REVENUE))->toBeNull();
});

it('de-duplicates entries using custom presets', function () {
    $template = new class extends AccountingTemplate
    {
        public function configure(): void
        {
            $this->debit(CustomPreset::SECONDARY_BANK, fn ($amount) => 40)
                ->debit(CustomPreset::SECONDARY_BANK, fn ($amount) => 60)
                ->credit(CustomPreset::CONSULTING_REVENUE);
        }
    };
    $template->configure();
    $entries = $template->deDuplicate()->getEntries();

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->account)->toBe(CustomPreset::SECONDARY_BANK)
        ->and($entries[0]->getAmount(100))->toBe(100.0);
});

it('names the custom preset enum in the ambiguous parent error', function () {
    $parent = presetAccount('1000', AccountTypeEnum::ASSET, CustomPreset::SECONDARY_BANK);
    presetAccount('1001', AccountTypeEnum::ASSET, null, $parent);
    presetAccount('1002', AccountTypeEnum::ASSET, null, $parent);
    presetAccount('4000', AccountTypeEnum::INCOME, CustomPreset::CONSULTING_REVENUE);

    (new ConsultingTemplate)->make(new SaleModel);
})->throws(ParentAccountPostingException::class, 'resolveAccount(CustomPreset::SECONDARY_BANK');

it('reports custom presets as unused by the built-in transaction catalogue', function () {
    expect(TransactionTemplate::CASH_SALE->usesAccount(CustomPreset::SECONDARY_BANK))->toBeFalse()
        ->and(TransactionTemplate::CASH_SALE->getEntryTypeForAccount(CustomPreset::SECONDARY_BANK))->toBeNull()
        ->and(TransactionTemplate::CASH_SALE->usesAccount(AccountPreset::CASH))->toBeTrue();
});
