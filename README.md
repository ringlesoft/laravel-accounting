# Laravel Accounting

<p align="center">
<a href="https://packagist.org/packages/ringlesoft/laravel-accounting"><img src="https://img.shields.io/packagist/v/ringlesoft/laravel-accounting.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/ringlesoft/laravel-accounting"><img src="https://img.shields.io/packagist/dt/ringlesoft/laravel-accounting.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/ringlesoft/laravel-accounting/blob/main/LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="License"></a>
</p>

A robust, double-entry accounting engine for Laravel applications. Laravel Accounting provides a journal-centric, immutable accounting system that seamlessly integrates with your existing Laravel models through traits and templates.

## Overview

Laravel Accounting is a framework-agnostic accounting core designed to handle all fundamental accounting operations while remaining flexible and developer-driven. It provides:

- **Double-entry bookkeeping** - Every transaction maintains balanced debits and credits
- **Immutable accounting records** - Posted entries cannot be modified, only reversed or voided
- **Flexible integration** - Transform any Laravel model into an accounting event using the `Postable` trait
- **Template-based posting** - Define accounting templates to automatically generate journal entries
- **Multi-currency support** - Handle transactions in multiple currencies with exchange rates
- **Fiscal period management** - Organize transactions by fiscal periods with period locking
- **Comprehensive reporting** - Generate trial balances, ledgers, and financial statements
- **Multi-tenancy ready** - Built-in support for multi-tenant applications

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
  - [Chart of Accounts](#chart-of-accounts)
  - [Journal Entries](#journal-entries)
  - [Postable Models](#postable-models)
  - [Accounting Templates](#accounting-templates)
  - [Vouchers](#vouchers)
- [Usage Guide](#usage-guide)
  - [Setting Up Accounts](#setting-up-accounts)
  - [Creating Journal Entries](#creating-journal-entries)
  - [Making Models Postable](#making-models-postable)
  - [Working with Templates](#working-with-templates)
  - [Posting Transactions](#posting-transactions)
  - [Reversing and Voiding](#reversing-and-voiding)
- [Advanced Features](#advanced-features)
  - [Fiscal Periods](#fiscal-periods)
  - [Multi-Currency](#multi-currency)
  - [Multi-Tenancy](#multi-tenancy)
  - [Account Balances](#account-balances)
- [Reporting](#reporting)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require ringlesoft/laravel-accounting
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laravel-accounting-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-accounting-config"
```

## Configuration

The configuration file `config/laravel_accounting.php` allows you to customize:

```php
return [
    // Database table prefix
    'table_prefix' => env('LARAVEL_ACCOUNTING_TABLE_PREFIX', 'la'),
    
    // Default currency code (ISO 4217)
    'default_currency_code' => env('LARAVEL_ACCOUNTING_CURRENCY', 'USD'),
    
    // Transaction number prefix
    'transaction_number_prefix' => env('LARAVEL_ACCOUNTING_TX_PREFIX', 'JE'),
    
    // Multi-tenancy settings
    'multi_tenancy' => [
        'enabled' => false,
        'tenant_id_column' => 'tenant_id',
    ],
    
    // Fiscal year settings
    'fiscal_year_start_month' => 1, // January
];
```

## Quick Start

### 1. Set Up Your Chart of Accounts

```bash
php artisan accounting:seed-accounts
```

This command seeds your database with a standard chart of accounts based on IFRS/US GAAP.

### 2. Create a Postable Model

Make any Laravel model postable by implementing the `PostableModel` interface and using the `Postable` trait:

```php
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Traits\Postable;

class Invoice extends Model implements PostableModel
{
    use Postable;
    
    public function getAmount(): float
    {
        return $this->total;
    }
    
    public function getReference(): ?string
    {
        return $this->invoice_number;
    }
}
```

### 3. Create an Accounting Template

Define how your model should be posted to the journal:

```php
use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class InvoiceTemplate extends AccountingTemplate
{
    public function configure(): void
    {
        $this->debit(AccountPreset::ACCOUNTS_RECEIVABLE)
             ->credit(AccountPreset::SALES_REVENUE)
             ->describe('Invoice payment received');
    }
}
```

### 4. Post to the Journal

```php
$invoice = Invoice::find(1);
$invoice->postWithTemplate(new InvoiceTemplate());

// Or if you've set a default template via attribute
$invoice->post();
```

## Core Concepts

### Chart of Accounts

The Chart of Accounts is the foundation of your accounting system. Each account has:

- **Code** - Unique identifier (e.g., "1010", "4000")
- **Name** - Descriptive name (e.g., "Cash", "Sales Revenue")
- **Type** - Asset, Liability, Equity, Income, or Expense
- **Preset** - Standardized account identifier for templates

```php
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

// Find an account by preset
$cashAccount = Account::where('preset', AccountPreset::CASH->value)->first();

// Create a custom account
Account::create([
    'code' => '1015',
    'name' => 'Petty Cash',
    'account_type_id' => $assetType->id,
    'currency_code' => 'USD',
    'is_active' => true,
    'allows_manual_entries' => true,
]);
```

### Journal Entries

Journal entries are the core of double-entry bookkeeping. Each entry contains:

- **Transaction Date** - When the transaction occurred
- **Description** - What the transaction represents
- **Items** - Individual debit and credit lines
- **Status** - Draft, Posted, Voided, or Reversed

```php
use RingleSoft\LaravelAccounting\Models\JournalEntry;

// Query journal entries
$entries = JournalEntry::posted()
    ->notVoided()
    ->byDateRange('2024-01-01', '2024-12-31')
    ->get();

// Check if balanced
if ($entry->isBalanced()) {
    $entry->post();
}
```

### Postable Models

Any Laravel model can become a source of accounting transactions by implementing `PostableModel`:

```php
interface PostableModel
{
    public function getAmount(): float;        // Transaction amount
    public function getReference(): ?string;   // Reference number
    public function toVoucher(): VoucherData; // Convert to voucher
    public function post(): bool;              // Post to journal
    public function isPosted(): bool;          // Check if posted
}
```

The `Postable` trait provides default implementations for most methods.

### Accounting Templates

Templates define the accounting logic for posting transactions. They map business events to journal entries:

```php
class PaymentTemplate extends AccountingTemplate
{
    public function configure(): void
    {
        $this->debit(AccountPreset::CASH, fn($amount) => $amount)
             ->credit(AccountPreset::ACCOUNTS_RECEIVABLE, fn($amount) => $amount)
             ->describe(fn($model) => "Payment received for {$model->reference}")
             ->date(fn($model) => $model->payment_date);
    }
}
```

Templates support:
- **Dynamic amounts** - Use closures to calculate amounts
- **Conditional logic** - Add entries based on model state
- **Multiple entries** - Complex transactions with many lines
- **Metadata** - Attach additional data to entries

### Vouchers

Vouchers are intermediate data structures that represent accounting transactions before they're posted:

```php
use RingleSoft\LaravelAccounting\DTOs\VoucherData;

$voucher = VoucherData::make()
    ->debit($cashAccountId, 1000.00, 'Payment received')
    ->credit($receivableAccountId, 1000.00, 'Clear receivable')
    ->on(now())
    ->describe('Customer payment')
    ->reference('PMT-001');

// Validate the voucher
$voucher->validate();

// Create journal entry from voucher
LaravelAccounting::journals()->createDraft($voucher);
```

## Usage Guide

### Setting Up Accounts

#### Seed Standard Accounts

```bash
php artisan accounting:seed-accounts
```

#### Create Custom Accounts

```php
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\AccountType;

$accountType = AccountType::where('name', 'Asset')->first();

Account::create([
    'code' => '1020',
    'name' => 'Business Checking Account',
    'account_type_id' => $accountType->id,
    'category' => 'Current Assets',
    'currency_code' => 'USD',
    'is_active' => true,
    'allows_manual_entries' => true,
    'preset' => 'CASH_IN_BANK',
]);
```

#### Hierarchical Accounts

```php
$parentAccount = Account::create([
    'code' => '1000',
    'name' => 'Current Assets',
    // ...
]);

$childAccount = Account::create([
    'code' => '1010',
    'name' => 'Cash',
    'parent_id' => $parentAccount->id,
    // ...
]);

// Get full hierarchical code
echo $childAccount->getFullCode(); // "1000.1010"
```

### Creating Journal Entries

#### Manual Journal Entry

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;

$voucher = VoucherData::make()
    ->debit($expenseAccountId, 500.00, 'Office supplies')
    ->credit($cashAccountId, 500.00, 'Payment for supplies')
    ->on(now())
    ->describe('Office supplies purchase')
    ->reference('EXP-001');

$journalEntry = LaravelAccounting::journals()->createDraft($voucher);
$journalEntry->post();
```

#### Using the Journal Service

```php
use RingleSoft\LaravelAccounting\Services\JournalService;

$journalService = app(JournalService::class);

// Create and post in one step
$entry = $journalService->createAndPost($voucherData, auth()->id());

// Reverse an entry
$reversalEntry = $journalService->reverse($originalEntry, 'Correction needed');

// Void an entry
$journalService->void($entry, 'Duplicate entry', auth()->id());
```

### Making Models Postable

#### Basic Implementation

```php
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Traits\Postable;

class Sale extends Model implements PostableModel
{
    use Postable;
    
    public function getAmount(): float
    {
        return $this->total_amount;
    }
    
    public function getReference(): ?string
    {
        return $this->sale_number;
    }
}
```

#### With Default Template

```php
use RingleSoft\LaravelAccounting\Attributes\DefaultAccountingTemplate;

#[DefaultAccountingTemplate(SaleTemplate::class)]
class Sale extends Model implements PostableModel
{
    use Postable;
    
    public function getAmount(): float
    {
        return $this->total_amount;
    }
}

// Now you can post without specifying template
$sale->post();
```

#### Check Posting Status

```php
if ($sale->isPosted()) {
    echo "Already posted to journal";
}

if ($sale->hasJournalEntry()) {
    $journalEntry = $sale->journalEntry;
    echo "Transaction #: {$journalEntry->transaction_number}";
}
```

### Working with Templates

#### Simple Template

```php
use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class SimpleInvoiceTemplate extends AccountingTemplate
{
    public function configure(): void
    {
        $this->debit(AccountPreset::ACCOUNTS_RECEIVABLE)
             ->credit(AccountPreset::SALES_REVENUE);
    }
}
```

#### Template with Dynamic Amounts

```php
class InvoiceWithTaxTemplate extends AccountingTemplate
{
    public function configure(): void
    {
        // Main sale amount (90% of total)
        $this->debit(AccountPreset::ACCOUNTS_RECEIVABLE)
             ->credit(AccountPreset::SALES_REVENUE, fn($amount) => $amount * 0.9)
             ->credit(AccountPreset::SALES_TAX_PAYABLE, fn($amount) => $amount * 0.1);
    }
}
```

#### Template with Conditional Logic

```php
class PaymentTemplate extends AccountingTemplate
{
    public function configure(): void
    {
        $this->debit(AccountPreset::CASH)
             ->credit(AccountPreset::ACCOUNTS_RECEIVABLE);
    }
    
    public function make(PostableModel $model): VoucherData
    {
        // Add discount if applicable
        if ($model->discount_amount > 0) {
            $this->debit(AccountPreset::SALES_DISCOUNTS, 
                fn($amount) => $model->discount_amount);
        }
        
        return parent::make($model);
    }
}
```

#### Template with Custom Description

```php
class ExpenseTemplate extends AccountingTemplate
{
    public function configure(): void
    {
        $this->debit(AccountPreset::RENT_EXPENSE)
             ->credit(AccountPreset::CASH)
             ->describe(fn($model) => "Rent payment for {$model->period}")
             ->date(fn($model) => $model->payment_date);
    }
}
```

### Posting Transactions

#### Post with Template

```php
$invoice = Invoice::find(1);
$template = new InvoiceTemplate();

if ($invoice->postWithTemplate($template)) {
    echo "Posted successfully!";
}
```

#### Post with Default Template

```php
try {
    $invoice->post();
    echo "Posted to journal entry: {$invoice->journalEntry->transaction_number}";
} catch (LaravelAccountingException $e) {
    echo "Failed to post: {$e->getMessage()}";
}
```

#### Two-Step Posting

```php
// Step 1: Create draft journal entry
$invoice->createJournal();

// Review the draft
$draft = $invoice->journalEntry;
echo "Total Debits: {$draft->getTotalDebits()}";
echo "Total Credits: {$draft->getTotalCredits()}";

// Step 2: Post the journal entry
if ($draft->canBePosted()) {
    $invoice->postJournal();
}
```

### Reversing and Voiding

#### Reverse a Journal Entry

Reversing creates a new entry with opposite debits/credits:

```php
$originalEntry = JournalEntry::find(1);

if ($originalEntry->canBeReversed()) {
    $reversalEntry = $originalEntry->reverse('Correction needed');
    echo "Created reversal: {$reversalEntry->transaction_number}";
}
```

#### Void a Journal Entry

Voiding marks an entry as cancelled without creating a reversal:

```php
use RingleSoft\LaravelAccounting\Services\JournalService;

$journalService = app(JournalService::class);
$journalService->void($entry, 'Duplicate entry', auth()->id());
```

## Advanced Features

### Fiscal Periods

Organize transactions by fiscal periods and control when periods can accept new entries:

```php
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;

// Create a fiscal period
$period = FiscalPeriod::create([
    'name' => 'Q1 2024',
    'start_date' => '2024-01-01',
    'end_date' => '2024-03-31',
    'is_closed' => false,
]);

// Close a period (prevents new entries)
$period->close();

// Lock a period (prevents any modifications)
$period->lock();

// Query entries by period
$entries = JournalEntry::byPeriod($period->id)->get();
```

### Multi-Currency

Handle transactions in multiple currencies:

```php
$voucher = VoucherData::make()
    ->debit($accountId, 1000.00)
    ->credit($accountId, 1000.00)
    ->currency(CurrencyCode::EUR)
    ->withMetadata(['exchange_rate' => 1.18]);

// Journal entries store currency and exchange rate
$entry = JournalEntry::create([
    'currency_code' => 'EUR',
    'exchange_rate' => 1.18,
    // ...
]);
```

### Multi-Tenancy

Enable multi-tenancy in configuration:

```php
// config/laravel_accounting.php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_id_column' => 'tenant_id',
],
```

All accounting models will automatically scope to the current tenant:

```php
// Queries automatically filtered by tenant
$accounts = Account::all(); // Only current tenant's accounts

// Explicitly query by tenant
$entries = JournalEntry::byTenant($tenantId)->get();
```

### Account Balances

Track account balances by period:

```php
use RingleSoft\LaravelAccounting\Models\AccountBalance;

// Get balance for an account in a period
$balance = AccountBalance::where('account_id', $accountId)
    ->where('fiscal_period_id', $periodId)
    ->first();

echo "Debit: {$balance->debit_balance}";
echo "Credit: {$balance->credit_balance}";
echo "Net: {$balance->net_balance}";

// Recalculate balances
$accountBalance->recalculate();
```

## Reporting

### Trial Balance

```php
use RingleSoft\LaravelAccounting\Services\ReportService;

$reportService = app(ReportService::class);

$trialBalance = $reportService->trialBalance(
    startDate: '2024-01-01',
    endDate: '2024-12-31',
    tenantId: auth()->user()->tenant_id
);

foreach ($trialBalance as $item) {
    echo "{$item['account_name']}: Debit {$item['debit']} | Credit {$item['credit']}";
}
```

### General Ledger

```php
$ledger = $reportService->generalLedger(
    accountId: $accountId,
    startDate: '2024-01-01',
    endDate: '2024-12-31'
);
```

### Balance Sheet

```php
$balanceSheet = $reportService->balanceSheet(
    asOfDate: '2024-12-31',
    tenantId: $tenantId
);
```

### Income Statement

```php
$incomeStatement = $reportService->incomeStatement(
    startDate: '2024-01-01',
    endDate: '2024-12-31',
    tenantId: $tenantId
);
```

## API Reference

### Facades

```php
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;

// Access journal service
LaravelAccounting::journals()->createDraft($voucher);
LaravelAccounting::journals()->post($entry, $userId);

// Access account service
LaravelAccounting::accounts()->findByPreset(AccountPreset::CASH);
```

### Models

- `Account` - Chart of accounts
- `AccountType` - Account type classification
- `AccountBalance` - Period-based account balances
- `JournalEntry` - Journal entry header
- `JournalEntryItem` - Individual debit/credit lines
- `FiscalPeriod` - Fiscal period management

### Services

- `JournalService` - Journal entry operations
- `AccountService` - Account management
- `ReportService` - Financial reporting
- `BalanceService` - Balance calculations

### Enums

- `AccountPreset` - Standard chart of accounts
- `AccountType` - Asset, Liability, Equity, Income, Expense
- `EntryType` - Debit or Credit
- `CurrencyCode` - ISO 4217 currency codes
- `NormalBalance` - Debit or Credit normal balance

### Exceptions

All exceptions extend `LaravelAccountingException`:

- `AccountNotFoundException`
- `AccountInactiveException`
- `AccountNotPostableException`
- `JournalNotBalancedException`
- `DuplicatePostingException`
- `PeriodClosedException`
- `InvalidVoucherException`

## Testing

```bash
composer test
```

Run tests with coverage:

```bash
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
