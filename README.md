# Laravel Accounting

<p align="center">
<a href="https://packagist.org/packages/ringlesoft/laravel-accounting"><img src="https://img.shields.io/packagist/v/ringlesoft/laravel-accounting.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/ringlesoft/laravel-accounting"><img src="https://img.shields.io/packagist/dt/ringlesoft/laravel-accounting.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/ringlesoft/laravel-accounting/blob/main/LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="License"></a>
</p>

A double-entry accounting engine for Laravel. 
Turn any model to a source of accounting transactions. Attach a `Postable` trait, map it to an `AccountingTemplate`, and posting produces a balanced, immutable journal entry.

- **Double-entry bookkeeping** — every transaction maintains balanced debits and credits
- **Immutable records** — posted entries can't be edited, only reversed or voided
- **Template-based posting** — 39 ready-made templates for common transactions (sales, purchases, payroll, depreciation, loans, equity...), or easily write your own
- **Multi-currency**, **multi-tenancy**, and **fiscal period** support
- **Reports**: Trial Balance, Balance Sheet, Income Statement, Cash Flow Statement, General Ledger, Account Ledger, Chart of Accounts

> **Project status**: Fully functional and under active development, not yet 1.0 (still improving based on feedback).

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Diving Deeper](#diving-deeper)
  - [Core Concepts](#core-concepts)
  - [Architecture](#architecture)
  - [Configuration](#configuration)
  - [Manual Installation and Resources Publishing](#manual-installation-and-resources-publishing)
  - [Posting Transactions](#posting-transactions)
  - [Reversing and Voiding](#reversing-and-voiding)
  - [Fiscal Periods](#fiscal-periods)
  - [Multi-Currency & Multi-Tenancy](#multi-currency--multi-tenancy)
  - [Reporting](#reporting)
  - [Troubleshooting & FAQ](#troubleshooting--faq)
  - [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Installation
1. Install using composer
```bash
composer require ringlesoft/laravel-accounting
```

2. Run the installer: it publishes the config, migrations and seeders, offers to run migrations, and offers to seed account types + a full chart of accounts + fiscal periods for the current year:

```bash
php artisan laravel-accounting:install
```

## Quick Start

### Option A. Using PHP attributes

Implement the `PostableModel` interface and the `getPostableAmount()` method. Apply the `#[DefaultAccountingTemplate]` attribute
```php
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Traits\Postable;
use RingleSoft\LaravelAccounting\Attributes\DefaultAccountingTemplate;
use RingleSoft\LaravelAccounting\Templates\CreditSaleTemplate;

#[DefaultAccountingTemplate(CreditSaleTemplate::class)]
class Invoice extends Model implements PostableModel
{
    use Postable;

    public function getPostableAmount(): float // You must implement this
    {
        return $this->total;
    }
}
```

Now you can post your model
```php
$invoice = Invoice::create(['total' => 5000]);
$invoice->post();
echo $invoice->journalEntry->transaction_number;
```

### Option B. Manual Template assigning
Post with an explicit template and overridden accounts instead of the default attribute:

```php
use RingleSoft\LaravelAccounting\Templates\CreditSaleTemplate;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
$invoice = Invoice::create(['total' => 5000]);
$invoice->postWithTemplate(new CreditSaleTemplate( cashAccount: AccountPreset::PETTY_CASH));
```
> Note: Accounts are never posted to a parent.


---

## Diving Deeper

The Quick Start gets a model posting. The rest of this README builds up the full mental model: what each piece is called and why ([Core Concepts](#core-concepts)), how they fit together ([Architecture](#architecture)), how to configure and install the package ([Configuration](#configuration), [Manual Installation](#manual-installation-and-resources-publishing)), the full posting/reversal/closing workflow ([Posting Transactions](#posting-transactions), [Reversing and Voiding](#reversing-and-voiding), [Fiscal Periods](#fiscal-periods)), cross-cutting concerns ([Multi-Currency & Multi-Tenancy](#multi-currency--multi-tenancy)), [Reporting](#reporting), and a [Troubleshooting & FAQ](#troubleshooting--faq) for when something doesn't behave as expected.

### Core Concepts

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

### Architecture

```mermaid
graph LR
    flowchartLR
    MODEL["Your Model<br/>PostableModel + Postable trait"]:::model
    TEMPLATE["AccountingTemplate<br/>debit()/credit() lines by AccountPreset"]:::template
    VOUCHER["VoucherData + VoucherDataItem[]<br/>Balanced DTO: debits, credits,<br/>date, description, currency"]:::dto
    JOURNAL["JournalEntry + JournalEntryItem[]<br/>Immutable once posted<br/>Only reverse() / void()"]:::ledger
    REPORTS["BalanceService / ReportService<br/>Cached balances, financial reports"]:::report
    RESOLVE["Preset resolution order<br/>1. resolveAccount()<br/>2. ResolvesAccountPresets<br/>3. Auto / fail-fast"]:::note

    MODEL -->|"post() / postWithTemplate()<br/>createJournal()"| TEMPLATE
    TEMPLATE -->|"make($model)<br/>presets → Account ids"| VOUCHER
    VOUCHER -->|"createDraft() → post()"| JOURNAL
    JOURNAL --> REPORTS

    TEMPLATE -.-> RESOLVE
    RESOLVE -.-> VOUCHER

    classDef model    fill:#6CB2EB,stroke:#2B6CB0,stroke-width:2px,color:#1a1a1a
    classDef template fill:#B8A9F0,stroke:#6B46C1,stroke-width:2px,color:#1a1a1a
    classDef dto      fill:#F6D365,stroke:#B7791F,stroke-width:2px,color:#1a1a1a
    classDef ledger   fill:#7DD6A0,stroke:#2F855A,stroke-width:2px,color:#1a1a1a
    classDef report   fill:#5FD3C4,stroke:#2C7A7B,stroke-width:2px,color:#1a1a1a
    classDef note     fill:#E2E8F0,stroke:#4A5568,stroke-width:2px,color:#1a1a1a
    linkStyle default stroke:#8b95a1,stroke-width:2px
```
> Note: Templates never touch the database directly. They only describe *which* presets to debit/credit and *how much*. You can create your own Templates by extending the `AcountingTemplate` class

### Configuration

The installer (or a manual `vendor:publish`, see below) publishes `config/laravel_accounting.php` into your Laravel application:

```php
return [
    'table_prefix' => 'la',                 // DB table prefix for this package's tables
    'multi_tenancy_field' => 'tenant_id',
    'strict_multi_tenancy_check' => true,   // false allows null-tenant rows as shared/global data
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

> Posting fails with a validation error ("the fiscal period id field is required") if no fiscal period covers today — make sure `FiscalPeriodSeeder` has run, or create one via `LaravelAccounting::periods()->createPeriodsForYear($fiscalYear)`.

### Manual Installation and Resources Publishing

If you don't want to run the install command, you can manually publish package resources. This is what the `install` command does in the background:

```bash
php artisan vendor:publish --tag="accounting-config"
php artisan vendor:publish --tag="accounting-migrations"
php artisan migrate
php artisan db:seed --class=AccountTypeSeeder
php artisan db:seed --class=ChartOfAccountsSeeder
php artisan db:seed --class=FiscalPeriodSeeder
```

### Posting Transactions

#### Via a template directly

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

#### Via `PostingService`

For common transactions, `PostingService` wraps the right template plus `createDraft()` + `post()` in one call:

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

LaravelAccounting::posting()->cashSale($sale, auth()->id());
LaravelAccounting::posting()->purchaseOnAccount($purchase, auth()->id());
LaravelAccounting::posting()->payroll($payrollRun, auth()->id());
```

See the **PostingService Method** column in [docs/available-templates.md](docs/available-templates.md) for every available method.

#### Two-step posting (review before posting)

```php
$invoice->createJournal();           // draft only
$draft = $invoice->journalEntry;
if ($draft->canBePosted()) {
    $invoice->postJournal();
}
```

#### Resolving ambiguous accounts (children of a preset)

Sometimes a preset (e.g. `COST_OF_GOODS_SOLD`) resolves to an account that has multiple children under it. Since accounts are never posted to a parent, posting then fails with `ParentAccountPostingException` — the package refuses to guess which child you meant. There are two ways to resolve it:

1. Pass the template → account mapping as you instantiate the template:
```php
$purchase->postWithTemplate(
    (new CashPurchaseTemplate())->resolveAccount(AccountPreset::COST_OF_GOODS_SOLD, $cogsSubAccount)
);
```
2. Implement `ResolvesAccountPresets` on the postable model and return the mapping account via `resolveAccountForPreset()`:

```php
use RingleSoft\LaravelAccounting\Contracts\ResolvesAccountPresets;

#[DefaultAccountingTemplate(PrepaidExpenseTemplate::class)]
class Invoice extends Model implements PostableModel, ResolvesAccountPresets
{
    use Postable;
    public function resolveAccountForPreset(AccountPreset $preset): Account|int|string|null
    {
        return $preset === AccountPreset::COST_OF_GOODS_SOLD ? $this->department->cogsAccount : null;
    }
}
```

> See **[docs/available-templates.md](docs/available-templates.md)** for the full catalog of 39 preset templates (sales, purchases, payroll, depreciation, loans, equity, inventory, bad debt).

### Reversing and Voiding

Posted entries are immutable — correct them by reversing (creates an offsetting entry, full audit trail) rather than editing:

```php
$entry = JournalEntry::find(1);

if ($entry->canBeReversed()) {       // true once posted
    $reversal = $entry->reverse('Correction needed');
}
```

`void()` only applies to a **draft, unposted** entry (cancelling it before it's ever posted) — once posted, `canBeVoided()` returns `false` and you must `reverse()` instead.

### Fiscal Periods

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\FiscalYear;

$year = FiscalYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
$periods = LaravelAccounting::periods()->createPeriodsForYear($year); // generates periods per fiscal_periods_count

LaravelAccounting::periods()->closePeriod($periods[0]); // recalculates & caches balances, then closes
LaravelAccounting::periods()->lockPeriod($periods[0]);  // freezes it completely (only unlock() can undo)
```

Posting validates the fiscal period is open; closing validates every entry in it is posted and the trial balance is in balance.

#### Closing entries

`PeriodService::closePeriod()` doesn't post anything by itself — it just validates and locks. To actually zero out Revenue/Expense into Retained Earnings before closing a period (the step that was missing before, and why the Balance Sheet wouldn't balance at year-end), use `ClosingService`:

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

$preview = LaravelAccounting::closing()->preview($period); // pure read, nothing posted
$preview->netIncome;        // signed: positive = profit, negative = loss
$preview->closeRevenue;     // the exact VoucherData that would be posted, or null if nothing to close

$result = LaravelAccounting::closing()->commit($period); // posts all four steps, through the normal JournalService path, in one transaction
```

Each of the four steps (close Revenue, close Expenses, close Income Summary → Retained Earnings, close Dividends → Retained Earnings) also has its own `previewCloseX()`/`commitCloseX()` pair if you want to run them independently rather than all at once — e.g. `commitCloseRevenue($period)`. Every step reads the ledger's actual current state, not a cached calculation, so calling them in any order, or calling the same one twice, is safe: a step with nothing left to close just returns `null`. Requires `INCOME_SUMMARY` and `RETAINED_EARNINGS` accounts to be seeded (throws `AccountNotFoundException` if not); a missing `DIVIDENDS_DECLARED` account just means that step is skipped, since most businesses never declare dividends at all. Must run before `PeriodService::closePeriod()`, since posting is rejected once a period is closed.

### Multi-Currency & Multi-Tenancy

```php
$voucher = VoucherData::make()
    ->debit($accountId, 1000.00)
    ->credit($accountId, 1000.00)
    ->currency(\RingleSoft\LaravelAccounting\Enums\CurrencyCode::EUR)
    ->withMetadata(['exchange_rate' => 1.18]);
```

Multi-tenancy isn't a toggle — any model using the `MultiTenant` trait (Account, JournalEntry, FiscalYear, FiscalPeriod, AccountBalance all do) is always scoped to a `tenant_id` column via a global scope. `multi_tenancy_field` only controls which attribute is read off the authenticated user to populate it on create (defaults to `tenant_id`); if there's no authenticated user (queues, CLI) it's left unset.

By default (`strict_multi_tenancy_check => true`), every record must belong to exactly one resolvable tenant: a row with a `null` `tenant_id` is never visible to an authenticated tenant, and creating a record with no resolvable tenant_id (no authenticated user, and none set explicitly) throws `TenantRequiredException`.
Set it to `false` to allow genuinely global/shared records instead — `null`-tenant rows become visible to every tenant, and can be created without a resolvable tenant (e.g. from a seeder or console command) without being rejected.
Recommended is `true` whenever using multi-tenancy.

### Reporting

Every report goes through `LaravelAccounting::reports()->{reportType}($params)`, which returns an already-generated report - call `->getData()` (or `->toArray()`/`->toJson()`) directly. `$params` keys are snake_case and vary per report:

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

$trialBalance = LaravelAccounting::reports()
    ->trialBalance(['as_of_date' => now()])
    ->getData();

$incomeStatement = LaravelAccounting::reports()
    ->incomeStatement(['start_date' => now()->startOfMonth(), 'end_date' => now()->endOfMonth()])
    ->getData();

$ledger = LaravelAccounting::reports()
    ->accountLedger(['account_id' => $accountId, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31'])
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

Results are cached per `report_cache_ttl`, keyed by report type + params, and invalidated automatically whenever a journal entry posts, reverses, or voids - `LaravelAccounting::reports()->clearCache($reportType)` clears it manually if needed. Calling `->generate()` again on a returned report is safe but recomputes live and bypasses the cache for that call, so there's no need to.

### Troubleshooting & FAQ

**"Failed to post to journal. The fiscal period id field is required."**
No fiscal period covers the transaction date. Seed one (`FiscalPeriodSeeder`) or create it directly: `LaravelAccounting::periods()->createPeriodsForYear($fiscalYear)`.

**`ArgumentCountError` when calling `TemplateRegistry::make('key')` or `YourTemplate::getPostableAccounts()`**
Both call `new $class()` with zero arguments. Every constructor parameter on a template needs a default value — see **Gotchas** in [docs/available-templates.md](docs/available-templates.md).

**`ParentAccountPostingException`**
A preset (e.g. `COST_OF_GOODS_SOLD`, `ACCOUNTS_RECEIVABLE`) resolved to a parent/category account with more than one child, so the package refuses to guess which one to post to — accounts are never posted to a parent. Pick one with `resolveAccount()`, or implement `ResolvesAccountPresets` on the model — see [Posting Transactions](#posting-transactions) above.

**`AccountNotFoundException`**
No seeded account carries the preset a template needs. Check your chart of accounts, or verify with `YourTemplate::getPostableAccounts()`.

**`JournalNotBalancedException`**
Debits ≠ credits. Common causes: a template's `configure()` was called twice (entries only ever append, never replace — never call `->configure()` yourself), or a closure-based amount produced an unexpected value.

**Why doesn't my Balance Sheet balance at year-end?**
Run `LaravelAccounting::closing()->commit($period)` before closing the period — see **Closing entries** under [Fiscal Periods](#fiscal-periods) above. Nothing runs this automatically; `PeriodService::closePeriod()` won't do it for you. `BalanceService::calculateUnclosedEarnings()` still exists as a lower-level primitive if you need the unclosed-earnings figure without actually posting anything.

**Is multi-tenancy optional?**
No — it's not a config toggle. Any model using the `MultiTenant` trait (`Account`, `JournalEntry`, `FiscalYear`, `FiscalPeriod`, `AccountBalance`) is always scoped to `tenant_id` via a global scope. `multi_tenancy_field` only controls which attribute is read off the authenticated user to populate it.

**The tenant scope relies on `Auth::user()` — what happens in a queued job or artisan command, where there's no authenticated user?**
No tenant filter is applied at all in that context — the global scope only ever narrows results to the authenticated user's own tenant; it never fails open onto *other* tenants' data. Under the default strict check it never matches a `null`-`tenant_id` row either. But without an authenticated user, there's nothing for it to scope to, so a queued job or console command operating across tenants is **your responsibility to scope explicitly** (e.g. `Account::where('tenant_id', $tenantId)->get()`, or set `tenant_id` yourself before saving). Don't assume the package will scope non-HTTP code for you.

**I want some accounts/data shared across every tenant (e.g. a default chart of accounts), not owned by just one.**
Set `strict_multi_tenancy_check` to `false`. A record with no resolvable tenant — created explicitly with `tenant_id = null`, or from a seeder/console command with no authenticated user — is then allowed to exist and becomes visible to every tenant, instead of being rejected at creation and invisible everywhere except to whoever created it outside an authenticated context. Tenant-owned rows (a real `tenant_id` set) are still isolated from each other exactly as before — this only changes how `null`-tenant rows behave.

**Can I use cash-basis instead of accrual accounting?**
The `cash_basis` config flag is informational only — it isn't enforced anywhere. All shipped templates post on an accrual basis (revenue/expense recognized when earned/incurred, not when cash moves). See **Accounting Basis: Accrual vs Cash** in [docs/available-templates.md](docs/available-templates.md) before building cash-basis behavior yourself.

**UUIDs or auto-incrementing IDs?**
A one-time choice at install (`config('laravel_accounting.use_uuid')`). Switching after you have real data requires a manual data migration — decide before going to production.

**How do I write my own template?**
See **Creating Custom Templates** in [docs/available-templates.md](docs/available-templates.md) — covers registration, `validate()`, testing it without a database, and common gotchas.

**Does the package handle authorization (who's allowed to post, close a period, etc.)?**
No — that's left entirely to your application. There are no policy classes or permission checks anywhere in the package; wrap calls into `PostingService`/`PeriodService`/`JournalService` with your own authorization logic (a policy, a middleware, a form request) before invoking them.

### API Reference

#### Facade

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

LaravelAccounting::accounts();   // AccountService
LaravelAccounting::balances();   // BalanceService
LaravelAccounting::journals();   // JournalService
LaravelAccounting::reports();    // ReportService
LaravelAccounting::posting();    // PostingService
LaravelAccounting::periods();    // PeriodService
LaravelAccounting::templates();  // TemplateRegistry
LaravelAccounting::closing();    // ClosingService
LaravelAccounting::chartsOfAccounts();
```

#### Models

`Account`, `AccountType`, `AccountBalance` (cached per-period balances), `JournalEntry`, `JournalEntryItem`, `FiscalYear`, `FiscalPeriod`, `ExchangeRate`.

#### Services

`JournalService` (create/validate/post/reverse/void), `AccountService` (CRUD, hierarchy, preset lookup), `BalanceService` (balance calculation, trial balance, net income), `ReportService`, `PostingService` (named transaction shortcuts), `PeriodService` (open/close/lock fiscal periods), `ClosingService` (preview/commit period-end closing entries).

#### Key Exceptions

All extend `LaravelAccountingException`: `AccountNotFoundException`, `AccountInactiveException`, `ParentAccountPostingException` (preset resolves to a parent/category account with no unambiguous child), `JournalNotBalancedException`, `DuplicatePostingException`, `PeriodClosedException`, `InvalidVoucherException`, `TemplateNotFoundException`. See `src/Exceptions/` for the full list.

## Testing

```bash
composer test
composer test-coverage
```

##  Contributing

Let me pave the way, I'll let you know when you can contribute. 

## Security

If you discover any security-related issues, please email ringunger@gmail.com or use the issue tracker.

## Credits

- [David Ringle](https://github.com/ringlesoft) - Developer

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support
- **Issues**: [https://github.com/ringlesoft/laravel-accounting/issues](https://github.com/ringlesoft/laravel-accounting/issues)
- [Buy me a Coffee](https://www.buymeacoffee.com/ringunger)
- [Github Sponsors](https://github.com/sponsors/ringlesoft)

## Contacts
Follow me on <a href="https://x.com/ringunger">X</a>: <a href="https://x.com/ringunger">@ringunger</a><br>
Email me: <a href="mailto:ringunger@gmail.com">ringunger@gmail.com</a><br>
Website: [https://github.com/ringlesoft/laravel-accounting](https://github.com/ringlesoft/laravel-accounting)
