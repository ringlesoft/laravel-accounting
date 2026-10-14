# Laravel Accounting

<p align="center">
<a href="https://packagist.org/packages/ringlesoft/laravel-accounting"><img src="https://img.shields.io/packagist/v/ringlesoft/laravel-accounting.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/ringlesoft/laravel-accounting"><img src="https://img.shields.io/packagist/dt/ringlesoft/laravel-accounting.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/ringlesoft/laravel-accounting/blob/main/LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="License"></a>
</p>

A double-entry accounting engine for Laravel. Any model can become a source of accounting transactions — attach a `Postable` trait, map it to an `AccountingTemplate`, and posting produces a balanced, immutable journal entry.

- **Double-entry bookkeeping** — every transaction maintains balanced debits and credits
- **Immutable records** — posted entries can't be edited, only reversed or voided
- **Template-based posting** — 23 ready-made templates for common transactions (sales, purchases, payroll, depreciation, loans, equity...), or write your own
- **Multi-currency**, **multi-tenancy**, and **fiscal period** support
- **Reports**: Trial Balance, Balance Sheet, Income Statement, Cash Flow Statement, General Ledger, Account Ledger, Chart of Accounts

> **Project status**: functional and under active development, not yet 1.0 (currently `1.0.0-alpha`). See [roadmap.md](roadmap.md) for what's done and what's planned — in particular, there's no closing-entries mechanism yet, so Balance Sheet won't balance until the current period's net income is manually accounted for, and automated test coverage is thin. Treat this as a solid engine to build on, not a finished product.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
- [Posting Transactions](#posting-transactions)
- [Reversing and Voiding](#reversing-and-voiding)
- [Fiscal Periods](#fiscal-periods)
- [Multi-Currency & Multi-Tenancy](#multi-currency--multi-tenancy)
- [Reporting](#reporting)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Installation

```bash
composer require ringlesoft/laravel-accounting
```

Run the installer — it publishes the config, migrations and seeders, offers to run migrations, and offers to seed account types + a full chart of accounts + fiscal periods for the current year:

```bash
php artisan laravel-accounting:install
```

Or do it manually:

```bash
php artisan vendor:publish --tag="accounting-config"
php artisan vendor:publish --tag="accounting-migrations"
php artisan migrate

php artisan db:seed --class=AccountTypeSeeder
php artisan db:seed --class=ChartOfAccountsSeeder
php artisan db:seed --class=FiscalPeriodSeeder
```

Posting fails with a validation error ("the fiscal period id field is required") if no fiscal period covers today — make sure `FiscalPeriodSeeder` has run, or create one via `app(PeriodService::class)->createPeriodsForYear($fiscalYear)`.

## Configuration

`config/laravel_accounting.php`, published above:

```php
return [
    'table_prefix' => 'la',                 // DB table prefix for this package's tables
    'multi_tenancy_field' => 'tenant_id',
    'default_currency_code' => 'USD',
    'transaction_number_prefix' => 'JE',    // e.g. JE-20260101-000001
    'use_uuid' => false,                    // UUID vs auto-increment primary keys
    'default_fiscal_year_start' => ['month' => 1, 'day' => 1],
    'fiscal_periods_count' => 2,            // 12 monthly, 4 quarterly, 2 semi-annual, 1 annual
    'cash_basis' => false,                  // informational only, not enforced - see docs/available-templates.md
    'auto_post' => false,
    'balance_cache_enabled' => true,
    'report_cache_enabled' => true,
    'report_cache_ttl' => 3600,
    'cache_prefix' => 'laravel_accounting',
    'supported_currencies' => [],           // empty = allow any ISO 4217 code
    'account_code_format' => '/^[0-9]{4}(-[0-9]{2})?$/',
    'allow_posting_on_parents' => false,
];
```

## Quick Start

### 1. Make a model postable

```php
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Traits\Postable;
use RingleSoft\LaravelAccounting\Attributes\DefaultAccountingTemplate;
use RingleSoft\LaravelAccounting\Templates\CreditSaleTemplate;

#[DefaultAccountingTemplate(CreditSaleTemplate::class)]
class Invoice extends Model implements PostableModel
{
    use Postable;

    public function getAmount(): float
    {
        return $this->total;
    }
}
```

### 2. Post it

```php
$invoice = Invoice::create(['total' => 5000]);
$invoice->post(); // Dr Accounts Receivable, Cr Sales Revenue — using CreditSaleTemplate

echo $invoice->journalEntry->transaction_number;
```

Or post with an explicit template and overridden accounts instead of the default attribute:

```php
use RingleSoft\LaravelAccounting\Templates\CashSaleTemplate;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

$invoice->postWithTemplate(new CashSaleTemplate(
    cashAccount: AccountPreset::PETTY_CASH,
    taxRate: 0.15,
));
```

See **[docs/available-templates.md](docs/available-templates.md)** for the full catalog of 23 templates (sales, purchases, payroll, depreciation, loans, equity, inventory, bad debt) with their exact debit/credit accounts and parameters, and **[docs/financial-transactions.md](docs/financial-transactions.md)** for ~100 standard accounting transactions cross-referenced against what's actually implemented today.

## Core Concepts

- **Account** — a chart-of-accounts entry (`code`, `name`, `account_type_id`, optional `parent_id` for hierarchy, optional `preset` to tag it for template lookup).
- **AccountPreset** — an enum of ~250 standard account roles (`CASH`, `ACCOUNTS_RECEIVABLE`, `SALES_REVENUE`, ...) that templates reference instead of hardcoded IDs, so the same template works against any chart of accounts.
- **JournalEntry** / **JournalEntryItem** — the immutable ledger: a header plus its balanced debit/credit lines. Posted entries can only be reversed or voided, never edited.
- **VoucherData** — the intermediate DTO a template (or your own code) builds before it becomes a journal entry.
- **AccountingTemplate** — encapsulates the debit/credit pattern for a transaction type. Supports any number of lines per side (not just one-to-one), optional conditional lines (e.g. tax), and dynamic amounts via closures.
- **Postable trait** — gives any Eloquent model `toVoucher()`, `post()`, `postWithTemplate()`, `isPosted()`, `journalEntry()`.

```php
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

$cash = Account::where('preset', AccountPreset::CASH->value)->first();

$child = Account::create([
    'code' => '1010', 'name' => 'Petty Cash', 'parent_id' => $parent->id,
    'account_type_id' => $assetType->id, 'currency_code' => 'USD',
]);
echo $child->getFullCode(); // hierarchical code, e.g. "1000.1010"
```

## Posting Transactions

### Via a template directly

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;

$voucher = VoucherData::make()
    ->debit($cashAccountId, 500.00, 'Office supplies')
    ->credit($expenseAccountId, 500.00)
    ->describe('Office supplies purchase')
    ->reference('EXP-001');

$entry = LaravelAccounting::journals()->createDraft($voucher);
LaravelAccounting::journals()->post($entry, auth()->id());
```

### Via `PostingService`

For common transactions, `PostingService` wraps the right template plus `createDraft()` + `post()` in one call:

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

LaravelAccounting::posting()->cashSale($sale, auth()->id());
LaravelAccounting::posting()->purchaseOnAccount($purchase, auth()->id());
LaravelAccounting::posting()->payroll($payrollRun, auth()->id());
```

See the **PostingService Method** column in [docs/available-templates.md](docs/available-templates.md) for every available method.

### Two-step posting (review before posting)

```php
$invoice->createJournal();           // draft only
$draft = $invoice->journalEntry;
if ($draft->canBePosted()) {
    $invoice->postJournal();
}
```

## Reversing and Voiding

Posted entries are immutable — correct them by reversing (creates an offsetting entry, full audit trail) rather than editing:

```php
$entry = JournalEntry::find(1);

if ($entry->canBeReversed()) {       // true once posted
    $reversal = $entry->reverse('Correction needed');
}
```

`void()` only applies to a **draft, unposted** entry (cancelling it before it's ever posted) — once posted, `canBeVoided()` returns `false` and you must `reverse()` instead.

## Fiscal Periods

```php
use RingleSoft\LaravelAccounting\Services\PeriodService;
use RingleSoft\LaravelAccounting\Models\FiscalYear;

$year = FiscalYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
$periods = app(PeriodService::class)->createPeriodsForYear($year); // generates periods per fiscal_periods_count

app(PeriodService::class)->closePeriod($periods[0]); // recalculates & caches balances, then closes
app(PeriodService::class)->lockPeriod($periods[0]);  // freezes it completely (only unlock() can undo)
```

Posting validates the fiscal period is open; closing validates every entry in it is posted and the trial balance is in balance.

## Multi-Currency & Multi-Tenancy

```php
$voucher = VoucherData::make()
    ->debit($accountId, 1000.00)
    ->credit($accountId, 1000.00)
    ->currency(\RingleSoft\LaravelAccounting\Enums\CurrencyCode::EUR)
    ->withMetadata(['exchange_rate' => 1.18]);
```

Multi-tenancy isn't a toggle — any model using the `MultiTenant` trait (Account, JournalEntry, FiscalYear, FiscalPeriod, AccountBalance all do) is always scoped to a `tenant_id` column via a global scope. `multi_tenancy_field` only controls which attribute is read off the authenticated user to populate it on create (defaults to `tenant_id`); if there's no authenticated user (queues, CLI) it's left unset.

## Reporting

Every report goes through `LaravelAccounting::reports()->{reportType}($params)->generate()`, then `->getData()` (or `->toArray()`/`->toJson()`). `$params` keys are snake_case and vary per report:

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

$trialBalance = LaravelAccounting::reports()
    ->trialBalance(['as_of_date' => now()])
    ->generate()
    ->getData();

$incomeStatement = LaravelAccounting::reports()
    ->incomeStatement(['start_date' => now()->startOfMonth(), 'end_date' => now()->endOfMonth()])
    ->generate()
    ->getData();

$ledger = LaravelAccounting::reports()
    ->accountLedger(['account_id' => $accountId, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31'])
    ->generate()
    ->getData();
```

| Report | Key params |
|---|---|
| Trial Balance | `as_of_date` |
| Balance Sheet | `as_of_date` |
| Income Statement | `start_date`, `end_date` |
| Cash Flow Statement | `start_date`, `end_date` |
| General Ledger | `start_date`, `end_date`, optional `account_id` |
| Account Ledger | `account_id` (required), `start_date`, `end_date` |
| Chart of Accounts | `include_inactive`, `account_type`, `show_balances`, `as_of_date` |

Results are cached per `report_cache_ttl`; `LaravelAccounting::reports()->clearCache($reportType)` clears it.

## API Reference

### Facade

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

LaravelAccounting::accounts();   // AccountService
LaravelAccounting::balances();   // BalanceService
LaravelAccounting::journals();   // JournalService
LaravelAccounting::reports();    // ReportService
LaravelAccounting::posting();    // PostingService
LaravelAccounting::periods();    // PeriodService
LaravelAccounting::templates();  // TemplateRegistry
LaravelAccounting::chartsOfAccounts();
```

### Models

`Account`, `AccountType`, `AccountBalance` (cached per-period balances), `JournalEntry`, `JournalEntryItem`, `FiscalYear`, `FiscalPeriod`, `ExchangeRate`.

### Services

`JournalService` (create/validate/post/reverse/void), `AccountService` (CRUD, hierarchy, preset lookup), `BalanceService` (balance calculation, trial balance, net income), `ReportService`, `PostingService` (named transaction shortcuts), `PeriodService` (open/close/lock fiscal periods).

### Key Exceptions

All extend `LaravelAccountingException`: `AccountNotFoundException`, `AccountInactiveException`, `JournalNotBalancedException`, `DuplicatePostingException`, `PeriodClosedException`, `InvalidVoucherException`, `TemplateNotFoundException`. See `src/Exceptions/` for the full list.

## Testing

```bash
composer test
composer test-coverage
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email ringunger@gmail.com instead of using the issue tracker.

## Credits

- [David Ringle](https://github.com/ringlesoft)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- **Documentation**: [https://github.com/ringlesoft/laravel-accounting](https://github.com/ringlesoft/laravel-accounting)
- **Issues**: [https://github.com/ringlesoft/laravel-accounting/issues](https://github.com/ringlesoft/laravel-accounting/issues)
- **Email**: ringunger@gmail.com
