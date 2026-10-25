<?php

use Database\Seeders\AccountTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RingleSoft\LaravelAccounting\Attributes\PresetAccount;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;
use RingleSoft\LaravelAccounting\Exceptions\InvalidPresetDefinitionException;
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\Account;

uses(RefreshDatabase::class);

require_once __DIR__.'/../database/seeders/AccountTypeSeeder.php';

enum SeededPreset: string implements AccountPresetContract
{
    // Declared before its parent, to check parents are seeded first.
    #[PresetAccount(name: 'Cloud Hosting', type: AccountTypeEnum::EXPENSE, parent: SeededPreset::IT_COSTS, code: '61010')]
    case CLOUD_HOSTING = 'CLOUD_HOSTING';

    #[PresetAccount(name: 'IT Costs', type: AccountTypeEnum::EXPENSE, parent: AccountPreset::OPERATING_EXPENSES, code: '61000', category: 'IT', allowsManualEntries: false)]
    case IT_COSTS = 'IT_COSTS';

    #[PresetAccount(name: 'Secondary Bank', type: AccountTypeEnum::ASSET, parent: AccountPreset::CASH_AND_CASH_EQUIVALENT, code: '10105')]
    case SECONDARY_BANK = 'SECONDARY_BANK';

    #[PresetAccount(name: 'Bank Overdraft Reserve', type: AccountTypeEnum::ASSET, parent: AccountPreset::CASH_AND_CASH_EQUIVALENT, normalBalance: NormalBalance::CREDIT)]
    case OVERDRAFT_RESERVE = 'OVERDRAFT_RESERVE';

    case RUNTIME_ONLY = 'RUNTIME_ONLY';
}

enum StandalonePreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'Licences', type: AccountTypeEnum::EXPENSE, parent: AccountPreset::OPERATING_EXPENSES)]
    case LICENCES = 'LICENCES';
}

enum ExplicitCodePreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'Subscriptions', type: AccountTypeEnum::EXPENSE, parent: AccountPreset::OPERATING_EXPENSES, code: '62000')]
    case SUBSCRIPTIONS = 'SUBSCRIPTIONS';
}

enum MisfitCodePreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'Misplaced', type: AccountTypeEnum::EXPENSE, parent: AccountPreset::OPERATING_EXPENSES, code: '10999')]
    case MISPLACED = 'MISPLACED';
}

enum CollidingPreset: string implements AccountPresetContract
{
    case CASH = 'CASH';
}

enum IntBackedPreset: int implements AccountPresetContract
{
    case ONE = 1;
}

enum NotAPreset: string
{
    case X = 'X';
}

enum RootPreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'My Root', type: AccountTypeEnum::ASSET, code: '80000')]
    case MY_ROOT = 'MY_ROOT';
}

enum SystemFlagPreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'Sneaky', type: AccountTypeEnum::ASSET, parent: AccountPreset::CURRENT_ASSETS, isSystemAccount: true)]
    case SNEAKY = 'SNEAKY';
}

enum DuplicateCodePreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'A', type: AccountTypeEnum::EXPENSE, parent: AccountPreset::OPERATING_EXPENSES, code: '63000')]
    case A = 'DUP_A';

    #[PresetAccount(name: 'B', type: AccountTypeEnum::EXPENSE, parent: AccountPreset::OPERATING_EXPENSES, code: '63000')]
    case B = 'DUP_B';
}

enum BuiltInCodePreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'Clash', type: AccountTypeEnum::ASSET, parent: AccountPreset::CURRENT_ASSETS, code: '10700')]
    case CLASH = 'CLASH';
}

enum CyclicPreset: string implements AccountPresetContract
{
    #[PresetAccount(name: 'A', type: AccountTypeEnum::EXPENSE, parent: CyclicPreset::B)]
    case A = 'CYCLE_A';

    #[PresetAccount(name: 'B', type: AccountTypeEnum::EXPENSE, parent: CyclicPreset::A)]
    case B = 'CYCLE_B';
}

beforeEach(function () {
    (new AccountTypeSeeder)->run();
});

function byPreset(AccountPresetContract $preset): ?Account
{
    return Account::where('preset', $preset->value)->first();
}

it('returns a list of presets for every industry', function () {
    $industries = AccountPreset::byIndustry();

    expect(array_keys($industries))->toBe(['full', 'retail', 'saas', 'manufacturing'])
        ->and($industries['full'])->toBe(AccountPreset::cases());

    foreach ($industries as $presets) {
        expect($presets)->toBeArray()->each->toBeInstanceOf(AccountPreset::class);
    }
});

it('seeds custom preset accounts together with the chart', function () {
    config()->set('laravel_accounting.custom_presets', [SeededPreset::class]);

    $created = LaravelAccounting::chart()->seed()['created'];

    expect($created)->toHaveCount(186 + 4)
        ->and(array_slice(array_keys($created), -4))->toBe(['IT_COSTS', 'CLOUD_HOSTING', 'SECONDARY_BANK', 'OVERDRAFT_RESERVE']);

    $bank = byPreset(SeededPreset::SECONDARY_BANK);
    $cashGroup = byPreset(AccountPreset::CASH_AND_CASH_EQUIVALENT);
    expect($bank->parent_id)->toBe($cashGroup->id)
        ->and($bank->code)->toBe('10105')
        ->and($bank->category)->toBe($cashGroup->category)
        ->and($bank->report_section)->toBe('Assets')
        ->and($bank->normal_balance)->toBe('DEBIT')
        ->and($bank->is_system_account)->toBeFalse();

    $it = byPreset(SeededPreset::IT_COSTS);
    expect(byPreset(SeededPreset::CLOUD_HOSTING)->parent_id)->toBe($it->id)
        ->and(byPreset(SeededPreset::CLOUD_HOSTING)->category)->toBe('IT')
        ->and($it->allows_manual_entries)->toBeFalse()
        ->and(byPreset(SeededPreset::OVERDRAFT_RESERVE)->code)->toBe('10106')
        ->and(byPreset(SeededPreset::OVERDRAFT_RESERVE)->normal_balance)->toBe('CREDIT')
        ->and(byPreset(SeededPreset::RUNTIME_ONLY))->toBeNull();

    // The seeded account is found by a template-side lookup.
    expect(LaravelAccounting::accounts()->findByPreset(SeededPreset::SECONDARY_BANK)->id)->toBe($bank->id);
});

it('seeds only custom presets into an existing chart', function () {
    LaravelAccounting::chart()->seed();
    config()->set('laravel_accounting.custom_presets', [StandalonePreset::class]);

    $created = LaravelAccounting::presets()->seed();

    expect(array_keys($created))->toBe(['LICENCES'])
        ->and($created['LICENCES']->code)->toBe('61000')
        ->and($created['LICENCES']->parent_id)->toBe(byPreset(AccountPreset::OPERATING_EXPENSES)->id);
});

it('is safe to re-run and never modifies existing custom accounts', function () {
    config()->set('laravel_accounting.custom_presets', [StandalonePreset::class]);
    LaravelAccounting::chart()->seed();
    byPreset(StandalonePreset::LICENCES)->update(['name' => 'Renamed by user']);

    expect(LaravelAccounting::chart()->seed(sync: true)['created'])->toBe([])
        ->and(byPreset(StandalonePreset::LICENCES)->name)->toBe('Renamed by user')
        ->and(Account::count())->toBe(187);
});

it('seeds each tenant separately', function () {
    config()->set('laravel_accounting.custom_presets', [StandalonePreset::class]);

    LaravelAccounting::chart()->seed(tenantId: 1);
    LaravelAccounting::chart()->seed(tenantId: 2);
    LaravelAccounting::chart()->seed(tenantId: 2);

    expect(Account::where('preset', 'LICENCES')->orderBy('tenant_id')->pluck('tenant_id')->all())->toBe([1, 2]);
});

it('rolls back everything when one account cannot be seeded', function () {
    // No chart: the custom accounts' built-in parents are missing.
    config()->set('laravel_accounting.custom_presets', [SeededPreset::class]);

    expect(fn () => LaravelAccounting::presets()->seed())
        ->toThrow(InvalidPresetDefinitionException::class, 'parent preset AccountPreset::OPERATING_EXPENSES has no account')
        ->and(Account::count())->toBe(0);
});

it('refuses to seed over a code another account already uses', function () {
    LaravelAccounting::chart()->seed();
    LaravelAccounting::accounts()->create([
        'code' => '62000', 'name' => 'Mine', 'account_type_id' => AccountTypeEnum::EXPENSE->getId(),
        'parent_id' => byPreset(AccountPreset::OPERATING_EXPENSES)->id,
    ]);
    config()->set('laravel_accounting.custom_presets', [ExplicitCodePreset::class]);

    LaravelAccounting::presets()->seed();
})->throws(InvalidPresetDefinitionException::class, "account code '62000' is already used by 'Mine'");

it('refuses a custom code that does not fit under its parent', function () {
    config()->set('laravel_accounting.custom_presets', [MisfitCodePreset::class]);

    LaravelAccounting::chart()->seed();
})->throws(InvalidPresetDefinitionException::class, "code '10999' doesn't fit under 'Operating Expenses'");

it('refuses a custom preset under an excluded parent', function () {
    config()->set('laravel_accounting.custom_presets', [SeededPreset::class]);
    config()->set('laravel_accounting.excluded_presets', [AccountPreset::CASH_AND_CASH_EQUIVALENT]);

    LaravelAccounting::chart()->definitions();
})->throws(InvalidPresetDefinitionException::class, "SeededPreset::SECONDARY_BANK's parent AccountPreset::CASH_AND_CASH_EQUIVALENT is excluded");

it('validates registered custom preset enums', function (array $enums, string $message) {
    config()->set('laravel_accounting.custom_presets', $enums);

    expect(fn () => LaravelAccounting::chart()->definitions())
        ->toThrow(InvalidPresetDefinitionException::class, $message);
})->with([
    'value collision with a built-in' => [[CollidingPreset::class], "CollidingPreset::CASH reuses the value 'CASH' of AccountPreset::CASH"],
    'int-backed' => [[IntBackedPreset::class], 'must be string-backed'],
    'not implementing the contract' => [[NotAPreset::class], 'must implement'],
    'not an enum' => [[stdClass::class], 'is not an enum'],
    'the built-in enum' => [[AccountPreset::class], "is the package's own chart"],
    'a root account' => [[RootPreset::class], 'RootPreset::MY_ROOT must name a parent'],
    'the system flag' => [[SystemFlagPreset::class], "can't set isSystemAccount"],
    'duplicate codes' => [[DuplicateCodePreset::class], "both define account code '63000'"],
    'a built-in code' => [[BuiltInCodePreset::class], "both define account code '10700'"],
    'parent cycle' => [[CyclicPreset::class], 'Preset parents form a cycle: CYCLE_A -> CYCLE_B -> CYCLE_A'],
]);

it('allows custom root accounts when the structure is not enforced', function () {
    config()->set('laravel_accounting.enforce_chart_structure', false);
    config()->set('laravel_accounting.custom_presets', [RootPreset::class]);

    expect(array_keys(LaravelAccounting::presets()->seed()))->toBe(['MY_ROOT']);
});

it('lists every case of the registered enums as presets', function () {
    config()->set('laravel_accounting.custom_presets', [SeededPreset::class, StandalonePreset::class]);

    expect(LaravelAccounting::presets()->presets())
        ->toBe([...SeededPreset::cases(), ...StandalonePreset::cases()]);

    config()->set('laravel_accounting.custom_presets', []);
    expect(LaravelAccounting::presets()->presets())->toBe([]);
});
