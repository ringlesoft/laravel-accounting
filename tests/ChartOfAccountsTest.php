<?php

use Database\Seeders\AccountTypeSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;
use RingleSoft\LaravelAccounting\Exceptions\ChartStructureException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidPresetDefinitionException;
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Support\AccountCodeGenerator;
use RingleSoft\LaravelAccounting\Support\PresetDefinitions;

uses(RefreshDatabase::class);

require_once __DIR__.'/../database/seeders/AccountTypeSeeder.php';
require_once __DIR__.'/../database/seeders/ChartOfAccountsSeeder.php';

/**
 * The structural accounts that earlier versions seeded without a preset.
 */
const STRUCTURAL_PRESETS = [
    '10000' => 'CURRENT_ASSETS', '15000' => 'NON_CURRENT_ASSETS', '20000' => 'CURRENT_LIABILITIES',
    '20500' => 'TAX_LIABILITIES', '20600' => 'PAYROLL_LIABILITIES', '25000' => 'LONG_TERM_LIABILITIES',
    '30000' => 'EQUITY', '40000' => 'REVENUE', '40100' => 'OPERATING_REVENUE', '40200' => 'OTHER_REVENUE',
    '40900' => 'SALES_RETURNS_AND_ALLOWANCES',
];

const ROOT_CODES = ['10000', '15000', '20000', '25000', '30000', '40000', '50000', '60000', '70000', '70100', '70200'];

beforeEach(function () {
    (new AccountTypeSeeder)->run();
});

function seedChart(): void
{
    (new ChartOfAccountsSeeder)->run();
}

function account(AccountPreset|string $presetOrCode): Account
{
    return $presetOrCode instanceof AccountPreset
        ? Account::where('preset', $presetOrCode->value)->firstOrFail()
        : Account::where('code', $presetOrCode)->firstOrFail();
}

function newAccount(array $attributes): Account
{
    return LaravelAccounting::accounts()->create($attributes + [
        'name' => 'New account',
        'account_type_id' => AccountTypeEnum::ASSET->getId(),
    ]);
}

// ---- The seeded chart

it('gives every seeded account a code that fits under its parent', function () {
    seedChart();
    $generator = app(AccountCodeGenerator::class);

    foreach (Account::whereNotNull('parent_id')->with('parent')->get() as $account) {
        expect($generator->fits($account->code, $account->parent, null, $account->id))
            ->toBeTrue("{$account->code} doesn't fit under {$account->parent->code}");
    }
});

it('locks exactly the eleven root accounts', function () {
    expect(PresetDefinitions::lockedValues())->toHaveCount(11);

    seedChart();

    expect(Account::whereNull('parent_id')->orderBy('code')->pluck('code')->all())->toBe(ROOT_CODES)
        ->and(Account::whereNull('parent_id')->get()->every(fn (Account $a) => PresetDefinitions::isLocked($a->preset)))->toBeTrue();
});

it('is safe to re-run', function () {
    seedChart();
    $result = LaravelAccounting::chart()->seed();

    expect($result['created'])->toBe([])
        ->and($result['adopted'])->toBe([])
        ->and(Account::count())->toBe(189);
});

it('seeds each tenant its own chart', function () {
    LaravelAccounting::chart()->seed(tenantId: 1);
    LaravelAccounting::chart()->seed(tenantId: 2);

    expect(Account::where('tenant_id', 1)->count())->toBe(189)
        ->and(Account::where('tenant_id', 2)->count())->toBe(189);
});

it('takes preset types and normal balances from the chart definitions', function () {
    expect(AccountPreset::OPERATING_EXPENSES->type())->toBe(AccountTypeEnum::EXPENSE)
        ->and(AccountPreset::CURRENT_LIABILITIES->type())->toBe(AccountTypeEnum::LIABILITY)
        ->and(AccountPreset::ACCUMULATED_DEPRECIATION->normalBalance())->toBe(NormalBalance::CREDIT)
        ->and(AccountPreset::CASH->normalBalance())->toBe(NormalBalance::DEBIT);
});

it('rejects an unknown industry', function () {
    LaravelAccounting::chart()->seed('aerospace');
})->throws(InvalidArgumentException::class, "Unknown industry 'aerospace'");

it('seeds an account for every preset the package\'s own code refers to', function () {
    seedChart();

    // Template defaults, ClosingService's accounts, report groups, ...
    $referenced = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src')) as $file) {
        if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), 'Enums')) {
            continue;
        }
        preg_match_all('/AccountPreset::([A-Z][A-Z_]+)\b/', file_get_contents($file->getPathname()), $matches);
        $referenced = [...$referenced, ...$matches[1]];
    }
    $referenced = array_values(array_unique($referenced));

    expect($referenced)->toContain('INCOME_SUMMARY', 'OUTPUT_VAT', 'BOND_ISSUANCE_COSTS')
        ->and(array_values(array_diff($referenced, Account::pluck('preset')->all())))->toBe([]);
});

it('seeds the closing, VAT and bond issuance accounts', function () {
    seedChart();

    expect(account(AccountPreset::INCOME_SUMMARY)->only(['code', 'is_system_account']))->toBe(['code' => '30900', 'is_system_account' => true])
        ->and(account(AccountPreset::INCOME_SUMMARY)->parent_id)->toBe(account(AccountPreset::EQUITY)->id)
        ->and(account(AccountPreset::OUTPUT_VAT)->parent_id)->toBe(account(AccountPreset::TAX_LIABILITIES)->id)
        ->and(account(AccountPreset::BOND_ISSUANCE_COSTS)->only(['code', 'normal_balance']))->toBe(['code' => '25100', 'normal_balance' => 'DEBIT']);
});

// ---- Optional accounts

it('seeds optional accounts only when included', function () {
    seedChart();
    expect(Account::where('preset', 'CASH_ON_HAND')->exists())->toBeFalse();

    config()->set('laravel_accounting.included_presets', [
        AccountPreset::CASH_ON_HAND, AccountPreset::ALLOWANCE_FOR_BAD_DEBTS->value,
        AccountPreset::WITHHOLDING_TAX_PAYABLE, AccountPreset::PENSION_LIABILITIES,
    ]);
    $created = LaravelAccounting::chart()->seed()['created'];

    expect(array_keys($created))->toEqualCanonicalizing(['CASH_ON_HAND', 'ALLOWANCE_FOR_BAD_DEBTS', 'WITHHOLDING_TAX_PAYABLE', 'PENSION_LIABILITIES'])
        ->and(account(AccountPreset::CASH_ON_HAND)->only(['code', 'parent_id']))->toBe(['code' => '10105', 'parent_id' => account(AccountPreset::CASH_AND_CASH_EQUIVALENT)->id])
        ->and(account(AccountPreset::ALLOWANCE_FOR_BAD_DEBTS)->normal_balance)->toBe('CREDIT')
        ->and(account(AccountPreset::WITHHOLDING_TAX_PAYABLE)->parent_id)->toBe(account(AccountPreset::TAX_LIABILITIES)->id)
        ->and(account(AccountPreset::PENSION_LIABILITIES)->parent_id)->toBe(account(AccountPreset::LONG_TERM_LIABILITIES)->id);
});

it('never lets a generated code take an explicitly coded sibling\'s code', function () {
    // CASH_ON_HAND (generated) is declared among CASH_AND_CASH_EQUIVALENT's
    // explicitly coded children; seeding in one pass must still succeed.
    config()->set('laravel_accounting.included_presets', [AccountPreset::CASH_ON_HAND]);
    seedChart();

    expect(account(AccountPreset::CASH_IN_BANK)->code)->toBe('10103')
        ->and(account(AccountPreset::CASH_ON_HAND)->code)->toBe('10105');
});

it('lets an exclusion win over an inclusion', function () {
    config()->set('laravel_accounting.excluded_presets', [AccountPreset::CASH_AND_CASH_EQUIVALENT]);
    config()->set('laravel_accounting.included_presets', [AccountPreset::CASH_ON_HAND]);
    seedChart();

    expect(Account::where('preset', 'CASH_ON_HAND')->exists())->toBeFalse();
});

it('rejects an unknown included preset', function () {
    config()->set('laravel_accounting.included_presets', ['NOPE']);

    LaravelAccounting::chart()->definitions();
})->throws(InvalidPresetDefinitionException::class, "included_presets: 'NOPE' isn't a preset");

// ---- Locked roots and no new roots

it('refuses any change to a root account', function (Closure $change) {
    seedChart();

    $change(account(AccountPreset::CURRENT_ASSETS));
})->throws(ChartStructureException::class)->with([
    'rename' => [fn (Account $a) => $a->update(['name' => 'Short-term Assets'])],
    'recode' => [fn (Account $a) => $a->update(['code' => '11000'])],
    'deactivate' => [fn (Account $a) => $a->update(['is_active' => false])],
    'move under another account' => [fn (Account $a) => $a->update(['parent_id' => account('15000')->id])],
    'delete' => [fn (Account $a) => $a->delete()],
]);

it('refuses to change a root account that carried a preset before', function () {
    seedChart();

    account(AccountPreset::OPERATING_EXPENSES)->update(['name' => 'Opex']);
})->throws(ChartStructureException::class, "can't be changed");

it('allows touching a root account and changing non-root accounts', function () {
    seedChart();

    account(AccountPreset::CURRENT_ASSETS)->touch();
    account(AccountPreset::CASH)->update(['name' => 'Cash on Hand']);
    account(AccountPreset::TAX_LIABILITIES)->update(['name' => 'Statutory Taxes']);

    expect(account(AccountPreset::CASH)->name)->toBe('Cash on Hand')
        ->and(account(AccountPreset::TAX_LIABILITIES)->name)->toBe('Statutory Taxes');
});

it('refuses to create a root account', function () {
    seedChart();

    newAccount(['code' => '80000']);
})->throws(ChartStructureException::class, 'needs a parent');

it('refuses to make an account a root', function () {
    seedChart();

    account(AccountPreset::CASH)->update(['parent_id' => null]);
})->throws(ChartStructureException::class, 'needs a parent');

it('refuses to give an account a root preset', function (Closure $action) {
    seedChart();

    $action();
})->throws(ChartStructureException::class)->with([
    'on create' => [fn () => newAccount(['parent_id' => account(AccountPreset::CURRENT_ASSETS)->id, 'preset' => 'EQUITY'])],
    'on update' => [fn () => account(AccountPreset::CASH)->update(['preset' => 'EQUITY'])],
]);

it('refuses another tenant\'s account as parent', function () {
    LaravelAccounting::chart()->seed(tenantId: 1);
    LaravelAccounting::chart()->seed(tenantId: 2);
    $otherTenantsCash = Account::where('tenant_id', 2)->where('preset', 'CASH_AND_CASH_EQUIVALENT')->value('id');
    FakeChartTenant::$tenant = 1;
    config()->set('laravel_accounting.tenant_resolver', FakeChartTenant::class);

    Account::create([
        'name' => 'Sneaky', 'account_type_id' => AccountTypeEnum::ASSET->getId(),
        'parent_id' => $otherTenantsCash, 'currency_code' => 'USD',
    ]);
})->throws(ChartStructureException::class, 'was not found');

class FakeChartTenant implements TenantResolver
{
    public static string|int|null $tenant = null;

    public function resolve(): string|int|null
    {
        return static::$tenant;
    }
}

it('allows root accounts and root changes when the structure is not enforced', function () {
    config()->set('laravel_accounting.enforce_chart_structure', false);
    seedChart();

    newAccount(['code' => '80000']);
    account(AccountPreset::CURRENT_ASSETS)->update(['name' => 'Short-term Assets']);

    expect(account('80000')->parent_id)->toBeNull()
        ->and(account(AccountPreset::CURRENT_ASSETS)->name)->toBe('Short-term Assets');
});

// ---- Code generation and validation

it('generates codes following the chart\'s scheme', function (AccountPreset $parent, array $expected) {
    seedChart();

    $codes = array_map(fn () => newAccount(['parent_id' => account($parent)->id])->code, $expected);

    expect($codes)->toBe($expected);
})->with([
    'root: steps by 100' => [AccountPreset::CURRENT_ASSETS, ['10800', '10900']],
    // 25100 is taken by BOND_ISSUANCE_COSTS, generated after 25001-25006.
    'root whose children step by 1: next hundred' => [AccountPreset::LONG_TERM_LIABILITIES, ['25200', '25300']],
    'root with no room for 100: steps by 1' => [AccountPreset::FINANCIAL_EXPENSES, ['70007', '70008']],
    'root leaf' => [AccountPreset::INCOME_TAX_EXPENSE, ['70101', '70102']],
    'second level: steps by 1' => [AccountPreset::CASH_AND_CASH_EQUIVALENT, ['10105', '10106']],
    'no room left: 2-digit suffix' => [AccountPreset::CASH, ['1010101', '1010102']],
]);

it('generates codes under a suffixed account', function () {
    seedChart();
    $bank = newAccount(['parent_id' => account(AccountPreset::CASH)->id]);

    expect(newAccount(['parent_id' => $bank->id])->code)->toBe('101010101');
});

it('validates an explicit code against the parent', function (string $code, bool $accepted, AccountPreset $parent = AccountPreset::CURRENT_ASSETS) {
    seedChart();
    $create = fn () => newAccount(['parent_id' => account($parent)->id, 'code' => $code]);

    if ($accepted) {
        expect($create()->code)->toBe($code);
    } else {
        expect($create)->toThrow(ChartStructureException::class, "doesn't fit");
    }
})->with([
    'free slot in range' => ['10750', true],
    'between siblings, past the earlier one\'s subtree' => ['10150', true],
    'suffix of the parent' => ['1000001', true],
    // 15300 Intangible Assets' subtree runs to 15314 (Accumulated Amortization's children).
    'inside a sibling\'s subtree' => ['15306', false, AccountPreset::NON_CURRENT_ASSETS],
    'outside the parent\'s range' => ['15050', false],
    'below the parent' => ['09999', false],
    'other length' => ['108', false],
    'suffix 00' => ['1000000', false],
]);

it('validates the code when an account is moved or re-coded', function () {
    seedChart();
    $account = newAccount(['parent_id' => account(AccountPreset::CURRENT_ASSETS)->id]);

    // A rejected update leaves its changes on the model, so start fresh each time.
    expect(fn () => $account->fresh()->update(['code' => '20900']))->toThrow(ChartStructureException::class)
        ->and(fn () => $account->fresh()->update(['parent_id' => account(AccountPreset::NON_CURRENT_ASSETS)->id]))->toThrow(ChartStructureException::class);

    $account->fresh()->update(['code' => '10850']);
    expect($account->fresh()->code)->toBe('10850');
});

it('still rejects a duplicate explicit code', function () {
    seedChart();

    newAccount(['parent_id' => account(AccountPreset::CURRENT_ASSETS)->id, 'code' => '10800']);
    newAccount(['parent_id' => account(AccountPreset::CURRENT_ASSETS)->id, 'code' => '10800']);
})->throws(InvalidArgumentException::class);

// ---- Upgrading charts seeded by earlier versions

it('adopts the structural accounts of a chart seeded by an earlier version', function () {
    seedChart();
    DB::table('la_accounts')->whereIn('code', array_keys(STRUCTURAL_PRESETS))->update(['preset' => null]);

    $result = LaravelAccounting::chart()->seed();

    expect(array_keys($result['adopted']))->toEqualCanonicalizing(array_values(STRUCTURAL_PRESETS))
        ->and($result['created'])->toBe([])
        ->and(account('10000')->preset)->toBe('CURRENT_ASSETS');
});

it('refuses to adopt an account of another type', function () {
    seedChart();
    DB::table('la_accounts')->where('code', '10000')->update([
        'preset' => null, 'account_type_id' => AccountTypeEnum::EXPENSE->getId(),
    ]);

    LaravelAccounting::chart()->seed();
})->throws(InvalidPresetDefinitionException::class, "account code '10000' is already used by 'Current Assets'");

// ---- Exclusions, overrides, sync

it('leaves out an excluded account with its whole branch', function () {
    config()->set('laravel_accounting.excluded_presets', [AccountPreset::CASH_AND_CASH_EQUIVALENT]);
    seedChart();

    expect(Account::whereIn('preset', ['CASH_AND_CASH_EQUIVALENT', 'CASH', 'PETTY_CASH', 'CASH_IN_BANK', 'UNDEPOSITED_FUNDS'])->count())->toBe(0)
        ->and(Account::where('preset', 'ACCOUNTS_RECEIVABLE')->exists())->toBeTrue()
        ->and(Account::count())->toBe(184);
});

it('applies preset overrides', function () {
    config()->set('laravel_accounting.preset_overrides', [
        AccountPreset::CASH->value => ['name' => 'Cash on Hand', 'description' => 'Till cash'],
        AccountPreset::DIGITAL_ASSETS->value => ['code' => '10750'],
    ]);
    seedChart();

    expect(account(AccountPreset::CASH)->only(['name', 'description']))->toBe(['name' => 'Cash on Hand', 'description' => 'Till cash'])
        ->and(account(AccountPreset::DIGITAL_ASSETS)->code)->toBe('10750');
});

it('rejects invalid exclusions and overrides', function (string $key, array $value, string $message) {
    config()->set("laravel_accounting.{$key}", $value);

    expect(fn () => LaravelAccounting::chart()->definitions())->toThrow(InvalidPresetDefinitionException::class, $message);
})->with([
    'excluding a root' => ['excluded_presets', ['EQUITY'], "root account EQUITY can't be excluded"],
    'excluding an unknown preset' => ['excluded_presets', ['NOPE'], "'NOPE' isn't a preset"],
    'overriding a root' => ['preset_overrides', ['EQUITY' => ['name' => 'Capital']], "root account EQUITY can't be overridden"],
    'overriding another field' => ['preset_overrides', ['CASH' => ['type' => 'Expense']], 'may only override name, code, description'],
    'overriding into a duplicate code' => ['preset_overrides', ['CASH' => ['code' => '10102']], "both define account code '10102'"],
]);

it('rejects an override code that does not fit', function () {
    config()->set('laravel_accounting.preset_overrides', [AccountPreset::CASH->value => ['code' => '20999']]);

    seedChart();
})->throws(InvalidPresetDefinitionException::class, "code '20999' doesn't fit");

it('refreshes descriptive fields only when syncing', function () {
    seedChart();
    account(AccountPreset::CASH)->update(['name' => 'Renamed by user']);

    LaravelAccounting::chart()->seed();
    expect(account(AccountPreset::CASH)->name)->toBe('Renamed by user');

    $result = LaravelAccounting::chart()->seed(sync: true);
    expect(account(AccountPreset::CASH)->name)->toBe('Cash')
        ->and(array_keys($result['synced']))->toBe(['CASH']);
});
