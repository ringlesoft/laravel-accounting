<?php

use Database\Seeders\AccountTypeSeeder;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\Account;

require_once __DIR__.'/../database/seeders/AccountTypeSeeder.php';

// Migrates its own database with UUID primary keys, so no RefreshDatabase.
beforeEach(function () {
    config()->set('laravel_accounting.use_uuid', true);
    $this->artisan('migrate:fresh');
    (new AccountTypeSeeder)->run();
});

it('returns UUID account type ids', function () {
    expect(AccountTypeEnum::ASSET->getId())
        ->toBeString()
        ->toBe(AccountTypeEnum::ASSET->accountType()->getKey());
});

it('seeds the chart and generates codes with UUID keys', function () {
    LaravelAccounting::chart()->seed();

    $bank = LaravelAccounting::accounts()->create([
        'name' => 'Bank B',
        'account_type_id' => AccountTypeEnum::ASSET->getId(),
        'parent_id' => LaravelAccounting::accounts()->findByPreset(AccountPreset::CASH_AND_CASH_EQUIVALENT)->getKey(),
    ]);

    expect(Account::count())->toBe(190)
        ->and($bank->getKey())->toBeString()
        ->and($bank->code)->toBe('10105');
});
