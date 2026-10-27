<?php

use Database\Seeders\AccountTypeSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Reports\AccountLedger;
use RingleSoft\LaravelAccounting\Reports\BalanceSheet;
use RingleSoft\LaravelAccounting\Reports\CashFlowStatement;
use RingleSoft\LaravelAccounting\Reports\ChartsOfAccounts;
use RingleSoft\LaravelAccounting\Reports\GeneralLedger;
use RingleSoft\LaravelAccounting\Reports\IncomeStatement;
use RingleSoft\LaravelAccounting\Reports\TrialBalance;
use RingleSoft\LaravelAccounting\Services\AccountService;
use RingleSoft\LaravelAccounting\Services\BalanceService;
use RingleSoft\LaravelAccounting\Services\ClosingService;
use RingleSoft\LaravelAccounting\Services\JournalService;
use RingleSoft\LaravelAccounting\Services\PeriodService;
use RingleSoft\LaravelAccounting\Support\Money;
use RingleSoft\LaravelAccounting\Templates\CashSaleTemplate;
use RingleSoft\LaravelAccounting\Traits\Postable;

uses(RefreshDatabase::class);

class PrecisionSaleModel extends Model implements PostableModel
{
    use Postable;

    public static string $amount = '0';

    public function getPostableAmount(): string
    {
        return static::$amount;
    }
}

beforeEach(function () {
    // These tests build a small standalone chart; the chart structure rules
    // are covered by ChartOfAccountsTest.
    config()->set('laravel_accounting.enforce_chart_structure', false);
    require_once __DIR__.'/../database/seeders/AccountTypeSeeder.php';
    (new AccountTypeSeeder)->run();
    app(PeriodService::class)->ensurePeriodsExist(0);

    $this->cash = precisionAccount('1000', AccountTypeEnum::ASSET, AccountPreset::CASH);
    $this->revenue = precisionAccount('4000', AccountTypeEnum::INCOME, AccountPreset::SALES_REVENUE);
    $this->expense = precisionAccount('6000', AccountTypeEnum::EXPENSE, null);
});

function precisionAccount(string $code, AccountTypeEnum $type, ?AccountPresetContract $preset): Account
{
    return app(AccountService::class)->create(array_filter([
        'code' => $code,
        'name' => 'Account '.$code,
        'account_type_id' => $type->getId(),
        'preset' => $preset?->value,
    ]));
}

function postVoucher(Account $debit, Account $credit, string $amount): JournalEntry
{
    $journals = app(JournalService::class);
    $entry = $journals->createDraft(VoucherData::make()->describe('Test')->debit($debit->id, $amount)->credit($credit->id, $amount));
    $journals->post($entry);

    return $entry;
}

it('stores and returns amounts with 4 decimal places', function () {
    $entry = postVoucher($this->cash, $this->revenue, '1234.5678');

    $item = $entry->fresh()->items->firstWhere('account_id', $this->cash->id);

    expect($item->debit_amount)->toBe('1234.5678')
        ->and($item->getAmount())->toBe('1234.5678')
        ->and($entry->getTotalDebits())->toBe('1234.5678')
        ->and(app(BalanceService::class)->getAccountBalance($this->cash))->toBe('1234.5678')
        ->and(app(BalanceService::class)->getAccountBalance($this->revenue))->toBe('1234.5678');
});

it('rejects a voucher that is off by 0.0001', function () {
    VoucherData::make()
        ->debit($this->cash->id, '100.0001')
        ->credit($this->revenue->id, '100.0000')
        ->validate();
})->throws(JournalNotBalancedException::class);

it('rejects a draft that is off by 0.0001 when posting', function () {
    $entry = app(JournalService::class)->createDraft(
        VoucherData::make()->describe('Test')->debit($this->cash->id, '50')->credit($this->revenue->id, '50')
    );
    $entry->items->firstWhere('account_id', $this->cash->id)->update(['debit_amount' => '50.0001']);

    app(JournalService::class)->post($entry);
})->throws(JournalNotBalancedException::class);

it('keeps sub-cent balances in the trial balance', function () {
    postVoucher($this->cash, $this->revenue, '0.0050');

    $report = (new TrialBalance([]))->generate()->getData();

    expect(collect($report['accounts'])->pluck('debit_balance', 'account_id')->get($this->cash->id))->toBe('0.0050')
        ->and($report['total_debits'])->toBe('0.0050')
        ->and($report['total_credits'])->toBe('0.0050')
        ->and($report['is_balanced'])->toBeTrue();
});

it('balances a tax-inclusive template exactly at 4 decimal places', function () {
    precisionAccount('2100', AccountTypeEnum::LIABILITY, AccountPreset::SALES_TAX_PAYABLE);
    // Rounding both lines separately gives 84.7458 + 15.2542 = 100.0000
    PrecisionSaleModel::$amount = '100.0001';

    $voucher = (new CashSaleTemplate(taxRate: 0.18))->make(new PrecisionSaleModel);
    $credits = collect($voucher->entries)->filter->isCredit()->pluck('amount')->all();

    expect($credits)->toBe(['84.7458', '15.2543'])
        ->and(Money::sum($credits))->toBe('100.0001');
});

it('closes sub-cent revenue and expense balances to exactly zero', function () {
    $incomeSummary = precisionAccount('3900', AccountTypeEnum::EQUITY, AccountPreset::INCOME_SUMMARY);
    $retainedEarnings = precisionAccount('3100', AccountTypeEnum::EQUITY, AccountPreset::RETAINED_EARNINGS);
    postVoucher($this->cash, $this->revenue, '100.0049');
    postVoucher($this->expense, $this->cash, '33.3333');

    $period = FiscalPeriod::where('start_date', '<=', now())->where('end_date', '>=', now())->firstOrFail();
    // Closing entries are dated on the period's last day, which can't be in the future
    $this->travelTo($period->end_date->copy()->addDay());
    $result = app(ClosingService::class)->commit($period);
    $balances = app(BalanceService::class);

    expect($result->totalRevenue)->toBe('100.0049')
        ->and($result->totalExpenses)->toBe('33.3333')
        ->and($result->netIncome)->toBe('66.6716')
        ->and($balances->getAccountBalance($this->revenue, $period->end_date))->toBe('0.0000')
        ->and($balances->getAccountBalance($this->expense, $period->end_date))->toBe('0.0000')
        ->and($balances->getAccountBalance($incomeSummary, $period->end_date))->toBe('0.0000')
        ->and($balances->getAccountBalance($retainedEarnings, $period->end_date))->toBe('66.6716');
});

it('reports 4-decimal amounts as strings', function () {
    postVoucher($this->cash, $this->revenue, '100.0049');
    postVoucher($this->expense, $this->cash, '33.3333');
    $range = ['start_date' => now()->startOfMonth(), 'end_date' => now()->endOfMonth()];

    $income = (new IncomeStatement($range))->generate()->getData();
    $balanceSheet = (new BalanceSheet([]))->generate()->getData();
    $cashFlow = (new CashFlowStatement($range))->generate()->getData();
    $ledger = (new AccountLedger($range + ['account_id' => $this->cash->id]))->generate()->getData();
    $general = (new GeneralLedger($range))->generate()->getData();
    $chart = (new ChartsOfAccounts(['show_balances' => true]))->generate()->getData();

    expect($income['revenue']['total'])->toBe('100.0049')
        ->and($income['expenses']['total'])->toBe('33.3333')
        ->and($income['net_income'])->toBe('66.6716')
        ->and($balanceSheet['assets']['total'])->toBe('66.6716')
        ->and($cashFlow['net_cash_flow'])->toBe('66.6716')
        ->and($cashFlow['operating_activities']['net_income'])->toBe('66.6716')
        ->and($ledger['total_debits'])->toBe('100.0049')
        ->and($ledger['total_credits'])->toBe('33.3333')
        ->and($ledger['closing_balance'])->toBe('66.6716')
        ->and(collect($ledger['transactions'])->last()['balance'])->toBe('66.6716')
        ->and($general['total_entries'])->toBe(4)
        ->and($chart)->toBeArray();
});
