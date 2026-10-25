<?php

use Database\Seeders\AccountTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\FiscalYear;
use RingleSoft\LaravelAccounting\Services\AccountService;
use RingleSoft\LaravelAccounting\Services\PeriodService;
use RingleSoft\LaravelAccounting\Validators\AccountValidator;

uses(RefreshDatabase::class);

class FakeTenantResolver implements TenantResolver
{
    public static string|int|null $tenant = null;

    public function resolve(): string|int|null
    {
        return static::$tenant;
    }
}

beforeEach(function () {
    config()->set('laravel_accounting.tenant_resolver', FakeTenantResolver::class);
    config()->set('laravel_accounting.strict_multi_tenancy_check', true);
    FakeTenantResolver::$tenant = null;

    require_once __DIR__.'/../database/seeders/AccountTypeSeeder.php';
    (new AccountTypeSeeder)->run();
});

function createAccount(string $code): Account
{
    return app(AccountService::class)->create([
        'code' => $code,
        'name' => 'Cash '.$code,
        'account_type_id' => AccountTypeEnum::ASSET->getId(),
    ]);
}

it('attributes and scopes records using the configured resolver', function () {
    FakeTenantResolver::$tenant = 1;
    createAccount('1000');

    FakeTenantResolver::$tenant = 2;
    createAccount('1001');

    expect(Account::pluck('code')->all())->toBe(['1001'])
        ->and(Account::first()->tenant_id)->toBe(2);
});

it('lets the resolved tenant override a caller supplied tenant_id', function () {
    FakeTenantResolver::$tenant = 1;

    $account = app(AccountService::class)->create([
        'code' => '1000',
        'name' => 'Cash',
        'account_type_id' => AccountTypeEnum::ASSET->getId(),
        'tenant_id' => 2,
    ]);

    expect($account->tenant_id)->toBe(1);
});

it('allows the same account code in different tenants', function () {
    FakeTenantResolver::$tenant = 1;
    createAccount('1000');

    FakeTenantResolver::$tenant = 2;
    createAccount('1000');

    expect(Account::withoutGlobalScopes()->where('code', '1000')->count())->toBe(2);
});

it('still rejects a duplicate code within one tenant', function () {
    FakeTenantResolver::$tenant = 1;
    createAccount('1000');

    createAccount('1000');
})->throws(InvalidArgumentException::class);

it('scopes the unique code validation rule to the tenant', function () {
    FakeTenantResolver::$tenant = 1;
    createAccount('1000');

    $data = [
        'code' => '1000',
        'name' => 'Cash',
        'account_type_id' => AccountTypeEnum::ASSET->getId(),
        'currency_code' => 'USD',
    ];

    expect(AccountValidator::make($data)->fails())->toBeTrue();

    FakeTenantResolver::$tenant = null;

    expect(AccountValidator::make($data + ['tenant_id' => 2])->fails())->toBeFalse()
        ->and(AccountValidator::make($data + ['tenant_id' => 1])->fails())->toBeTrue();
});

it('creates periods for a tenant with no resolved tenant and is idempotent', function () {
    config()->set('laravel_accounting.fiscal_periods_count', 4);
    $service = app(PeriodService::class);

    $service->ensurePeriodsExist(yearsAhead: 1, startMonth: 7, tenantId: 5);
    $service->ensurePeriodsExist(yearsAhead: 1, startMonth: 7, tenantId: 5);

    expect(FiscalYear::count())->toBe(2)
        ->and(FiscalPeriod::count())->toBe(8)
        ->and(FiscalYear::first()->start_date->month)->toBe(7)
        ->and(FiscalPeriod::whereNot('tenant_id', 5)->count())->toBe(0);
});

it('keeps fiscal years of different tenants separate', function () {
    $service = app(PeriodService::class);

    $service->ensurePeriodsExist(yearsAhead: 0, tenantId: 1);
    $service->ensurePeriodsExist(yearsAhead: 0, tenantId: 2);

    expect(FiscalYear::count())->toBe(2);
});
