<?php

use Database\Seeders\AccountTypeSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
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
require_once __DIR__.'/../database/seeders/ChartOfAccountsSeeder.php';

enum SeededPreset: string implements AccountPresetContract
{
    // Declared before its parent, to check parents are seeded first.
    #[PresetAccount(code: '61010', name: 'Cloud Hosting', type: AccountTypeEnum::EXPENSE, parent: SeededPreset::IT_COSTS)]
    case CLOUD_HOSTING = 'CLOUD_HOSTING';

    #[PresetAccount(code: '61000', name: 'IT Costs', type: AccountTypeEnum::EXPENSE, category: 'IT', allowsManualEntries: false)]
    case IT_COSTS = 'IT_COSTS';

    #[PresetAccount(code: '10105', name: 'Secondary Bank', type: AccountTypeEnum::ASSET, parent: AccountPreset::CASH_AND_CASH_EQUIVALENT)]
    case SECONDARY_BANK = 'SECONDARY_BANK';

    #[PresetAccount(code: '10106', name: 'Bank Overdraft Reserve', type: AccountTypeEnum::ASSET, normalBalance: NormalBalance::CREDIT)]
    case OVERDRAFT_RESERVE = 'OVERDRAFT_RESERVE';

    case RUNTIME_ONLY = 'RUNTIME_ONLY';
}

enum StandalonePreset: string implements AccountPresetContract
{
    #[PresetAccount(code: '62000', name: 'Licences', type: AccountTypeEnum::EXPENSE)]
    case LICENCES = 'LICENCES';
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

enum DuplicateCodePreset: string implements AccountPresetContract
{
    #[PresetAccount(code: '70000', name: 'A', type: AccountTypeEnum::EXPENSE)]
    case A = 'DUP_A';

    #[PresetAccount(code: '70000', name: 'B', type: AccountTypeEnum::EXPENSE)]
    case B = 'DUP_B';
}

enum CyclicPreset: string implements AccountPresetContract
{
    #[PresetAccount(code: '71000', name: 'A', type: AccountTypeEnum::EXPENSE, parent: CyclicPreset::B)]
    case A = 'CYCLE_A';

    #[PresetAccount(code: '71001', name: 'B', type: AccountTypeEnum::EXPENSE, parent: CyclicPreset::A)]
    case B = 'CYCLE_B';
}

class SeederWithout extends ChartOfAccountsSeeder
{
    public function runWithout(AccountPreset ...$excluded): void
    {
        $this->presets = array_values(array_diff(
            array_map(static fn (AccountPreset $preset) => $preset->value, AccountPreset::cases()),
            array_map(static fn (AccountPreset $preset) => $preset->value, $excluded),
        ));

        $this->seedAssets($this->getAccountTypes());
    }
}

beforeEach(function () {
    (new AccountTypeSeeder)->run();
});

function byPreset(AccountPresetContract $preset): ?Account
{
    return Account::where('preset', $preset->value)->first();
}

// ---- ChartOfAccountsSeeder fixes

it('seeds the full chart of accounts', function () {
    (new ChartOfAccountsSeeder)->run();

    expect(Account::count())->toBeGreaterThan(150)
        ->and(byPreset(AccountPreset::CASH)->parent_id)->toBe(byPreset(AccountPreset::CASH_AND_CASH_EQUIVALENT)->id);
});

it('skips the whole branch of an excluded parent preset instead of crashing', function () {
    (new SeederWithout)->runWithout(AccountPreset::CASH_AND_CASH_EQUIVALENT);

    expect(byPreset(AccountPreset::CASH_AND_CASH_EQUIVALENT))->toBeNull()
        ->and(byPreset(AccountPreset::CASH))->toBeNull()
        ->and(byPreset(AccountPreset::PETTY_CASH))->toBeNull()
        ->and(byPreset(AccountPreset::ACCOUNTS_RECEIVABLE))->not->toBeNull();
});

it('returns a list of presets for every industry', function () {
    $industries = AccountPreset::byIndustry();

    expect(array_keys($industries))->toBe(['full', 'retail', 'saas', 'manufacturing'])
        ->and($industries['full'])->toBe(AccountPreset::cases());

    foreach ($industries as $presets) {
        expect($presets)->toBeArray()->each->toBeInstanceOf(AccountPreset::class);
    }
});

it('rejects an unknown industry', function () {
    (new ChartOfAccountsSeeder)->run('aerospace');
})->throws(InvalidArgumentException::class, "Unknown industry 'aerospace'");

// ---- Custom preset seeding

it('seeds custom preset accounts under built-in and custom parents', function () {
    (new ChartOfAccountsSeeder)->run();
    config()->set('laravel_accounting.custom_presets', [SeededPreset::class]);

    $created = LaravelAccounting::presets()->seed();

    expect(array_keys($created))->toBe(['IT_COSTS', 'CLOUD_HOSTING', 'SECONDARY_BANK', 'OVERDRAFT_RESERVE']);

    $bank = byPreset(SeededPreset::SECONDARY_BANK);
    $cashGroup = byPreset(AccountPreset::CASH_AND_CASH_EQUIVALENT);
    expect($bank->parent_id)->toBe($cashGroup->id)
        ->and($bank->category)->toBe($cashGroup->category)
        ->and($bank->report_section)->toBe('Assets')
        ->and($bank->normal_balance)->toBe('DEBIT')
        ->and($bank->is_system_account)->toBeFalse();

    $it = byPreset(SeededPreset::IT_COSTS);
    expect(byPreset(SeededPreset::CLOUD_HOSTING)->parent_id)->toBe($it->id)
        ->and(byPreset(SeededPreset::CLOUD_HOSTING)->category)->toBe('IT')
        ->and($it->allows_manual_entries)->toBeFalse()
        ->and($it->normal_balance)->toBe('DEBIT')
        ->and(byPreset(SeededPreset::OVERDRAFT_RESERVE)->normal_balance)->toBe('CREDIT')
        ->and(byPreset(SeededPreset::RUNTIME_ONLY))->toBeNull();

    // The seeded account is found by a template-side lookup.
    expect(LaravelAccounting::accounts()->findByPreset(SeededPreset::SECONDARY_BANK)->id)->toBe($bank->id);
});

it('is safe to re-run and never modifies existing accounts', function () {
    config()->set('laravel_accounting.custom_presets', [StandalonePreset::class]);
    LaravelAccounting::presets()->seed();
    byPreset(StandalonePreset::LICENCES)->update(['name' => 'Renamed by user']);

    expect(LaravelAccounting::presets()->seed())->toBe([])
        ->and(byPreset(StandalonePreset::LICENCES)->name)->toBe('Renamed by user')
        ->and(Account::count())->toBe(1);
});

it('seeds each tenant separately', function () {
    config()->set('laravel_accounting.custom_presets', [StandalonePreset::class]);

    LaravelAccounting::presets()->seed(tenantId: 1);
    LaravelAccounting::presets()->seed(tenantId: 2);
    LaravelAccounting::presets()->seed(tenantId: 2);

    expect(Account::where('preset', 'LICENCES')->orderBy('tenant_id')->pluck('tenant_id')->all())->toBe([1, 2]);
});

it('rolls back everything when one account cannot be seeded', function () {
    // No chart of accounts: SECONDARY_BANK's built-in parent is missing.
    config()->set('laravel_accounting.custom_presets', [SeededPreset::class]);

    expect(fn () => LaravelAccounting::presets()->seed())
        ->toThrow(InvalidPresetDefinitionException::class, 'parent preset AccountPreset::CASH_AND_CASH_EQUIVALENT has no account')
        ->and(Account::count())->toBe(0);
});

it('refuses to seed over a code another account already uses', function () {
    config()->set('laravel_accounting.custom_presets', [StandalonePreset::class]);
    LaravelAccounting::accounts()->create([
        'code' => '62000', 'name' => 'Mine', 'account_type_id' => AccountTypeEnum::EXPENSE->getId(),
    ]);

    LaravelAccounting::presets()->seed();
})->throws(InvalidPresetDefinitionException::class, "account code '62000' is already used by 'Mine'");

it('validates registered custom preset enums', function (array $enums, string $message) {
    config()->set('laravel_accounting.custom_presets', $enums);

    expect(fn () => LaravelAccounting::presets()->definitions())
        ->toThrow(InvalidPresetDefinitionException::class, $message);
})->with([
    'value collision with a built-in' => [[CollidingPreset::class], "CollidingPreset::CASH reuses the value 'CASH' of AccountPreset::CASH"],
    'int-backed' => [[IntBackedPreset::class], 'must be string-backed'],
    'not implementing the contract' => [[NotAPreset::class], 'must implement'],
    'not an enum' => [[stdClass::class], 'is not an enum'],
    'duplicate codes' => [[DuplicateCodePreset::class], "both define account code '70000'"],
    'parent cycle' => [[CyclicPreset::class], 'Custom preset parents form a cycle: CYCLE_A -> CYCLE_B -> CYCLE_A'],
]);

it('lists every case of the registered enums as presets', function () {
    config()->set('laravel_accounting.custom_presets', [SeededPreset::class, StandalonePreset::class]);

    expect(LaravelAccounting::presets()->presets())
        ->toBe([...SeededPreset::cases(), ...StandalonePreset::cases()]);

    config()->set('laravel_accounting.custom_presets', []);
    expect(LaravelAccounting::presets()->presets())->toBe([]);
});
