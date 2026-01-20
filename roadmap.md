# Laravel Accounting

## Concept
This package aims to abstract the entire Accounting process in Laravel.
I'm developing a Laravel package that provides a complete, flexible accounting system foundation based on double-entry bookkeeping principles. The package will abstract core accounting operations—journal entries, chart of accounts management, multi-currency transactions, fiscal period handling, and audit trails—while giving developers the flexibility to customize account structures and business rules. It will generate IFRS-compliant financial reports (Balance Sheet, Income Statement, Cash Flow Statement) through a well-designed database schema that enforces the accounting equation, maintains immutable transaction records, and caches balances for performance. The goal is to provide a robust, production-ready accounting engine that developers can integrate into their Laravel applications without reinventing fundamental accounting mechanics.

Laravel Accounting is a framework-agnostic accounting engine designed to abstract all fundamental accounting operations while remaining fully flexible and developer-driven.
It provides a journal-centric, double-entry accounting kernel that can be integrated into any Laravel application via traits, contracts, and facades.

The package does not model business documents (e.g. invoices, payments) directly. Instead, it enables application models to declare themselves as postable accounting events, which are transformed into standardized vouchers and posted into the accounting ledger.

## Core Principles
- Separation of concerns
- Immutability
- Multi-tenant & multi-currency from day one
- Period-based accounting
  - Accounting periods (monthly, yearly)
  - Period locking (cannot post to closed periods)
  - Opening & closing balances

## Core Concepts to be covered
- Chart of Accounts (CoA)
  - Code (string, hierarchical: 1000, 1001, 1001-01)
  - Name
  - Type (Asset, Liability, Equity, Income, Expense)
  - Normal balance (Debit / Credit)
  - Parent account (for hierarchy)
  - Is_control_account (optional)
  - Is_active


## Domain Objects
- Account (CoA)
- Journal Entry
- Journal Entry Line Item
- Dimension (eg: Customer, Vendor, Project, Branch, Cost Center .... (not hard-coded))
- Ledger (Not stored)

## Core Operations 
- Posting transactions
  - Create draft journal
  - Validate balance
  - Validate period
  - Post journal (lock it)
  
- Reversals & adjustments
  - Full reversal
  - Partial adjustment
  - Backdated correction (with rules)

- Balance calculations
  - On demand:
    - Account balance as of date
    - Trial balance
    - Period movement vs opening balance

- Period management
  - Open period
  - Close period
  - Lock period
  - Roll forward balances

- Currency handling
  - Transaction currency
  - Base currency
  - Historical exchange rate
  - Realized vs unrealized FX gain/loss (advanced but important)

- Reporting (output layer)
  - At minimum:
    - Trial Balance
    - General Ledger
    - Profit & Loss
    - Balance Sheet
    - Cash Flow (indirect at least)



- Postable Architecture
  - Business models opt into accounting by using a Postable trait.
  - The trait enforces a toVoucher(): VoucherData contract
  - VoucherData is a standardized DTO representing a complete accounting intent
  - Posting is delegated to the accounting engine, not the model
  - This allows any domain model (Invoice, Expense, Payroll, Custom Object) to participate in accounting without coupling to accounting internals.
  
- Templates & Presets
The package ships with Postable Templates that simplify common accounting patterns:
  - Invoice
  - Purchase
  - Expense
  - Payment
  - Adjustment

  Templates pre-configure:
  - Debit and credit account mappings
  - Default journal types
  - Validation rules
  - Common posting behavior
Developers may extend or override templates to match custom accounting structures or country-specific requirements.


## General facts checks
- The package should generate data, not PDFs.
- Don’t store balances unless cached
- Don’t allow deletes
- Don’t assume one country
- Don’t assume one tax system
- Don’t mix business logic with accounting logic
- Use database transactions where appropriate


## Implementation insights

### Services
- JournalService
- PostingService
- BalanceService
- ReportService

### Events
- JournalEntryCreated
- JournalEntryPosted
- JournalEntryReversed
- JournalEntryAdjusted
- PeriodOpened
- PeriodClosed
- PeriodLocked

### Exceptions
- Period is closed
- Journal not balanced
- Account inactive
- Currency mismatch


## Artisan commanbds
- Install
- Close Period
- Update balances


## Shipped templates
- Invoice
- Purchase
- Expense
- Payment
- Adjustment

## Future Consideration
- Reconciliation
- Tax
- Accrual Basis Accounting
- Sub-Ledgers (Modular): Design these as optional, pluggable modules.
- Generate closing entries to reset revenue/expense accounts to retained earnings.

## Roadmap

---

## MVP Checklist - Path to Production Ready v1.0

### 1. Core Database Schema & Migrations ✓ (Completed)
- [x] **Implement UUID/ID Dynamic Support**
  - [x] Create helper method in migrations to use `$table->id()` or `$table->uuid('id')->primary()` based on config
  - [ ] Add `HasUuid` trait for models when UUID is enabled
  - [x] Update all migration files to use dynamic ID method
  - [x] Ensure foreign keys work with both UUID and integer IDs

- [x] **Complete `accounts` table migration**
  - [x] Add all fields: code, name, account_type_id, category, sub_category, report_section, parent_id, currency_id, is_active, is_system_account, allows_manual_entries, tenant_id
  - [x] Add proper indexes (code, parent_id, tenant_id, is_active)
  - [x] Add foreign key constraints
  - [x] Add unique constraint on (code, tenant_id)
  - [x] Use dynamic ID type based on config('laravel_accounting.use_uuid')

- [x] **Complete `journal_entries` table migration**
  - [x] Add all fields: transaction_number, transaction_date, posted_at, description, postable_id, postable_type, reference, currency_code, exchange_rate, fiscal_period_id, created_by_user_id, posted_by_user_id, is_voided, voided_at, voided_by_user_id, is_posted, is_reversed, reversal_of_id, tenant_id
  - [x] Add indexes (transaction_number, transaction_date, fiscal_period_id, postable_type/id, tenant_id)
  - [x] Add foreign key constraints
  - [ ] Implement auto-incrementing transaction_number logic (to be done in model/service)

- [x] **Complete `journal_entry_items` table migration**
  - [x] Add all fields: journal_entry_id, account_id, description, debit_amount, credit_amount, currency_code, exchange_rate, dimension_id, dimension_type, tenant_id
  - [x] Add indexes (journal_entry_id, account_id, tenant_id)
  - [x] Add foreign key constraints with cascade rules
  - [x] Add check constraint (debit_amount >= 0 AND credit_amount >= 0)

- [x] **Complete `fiscal_periods` table migration**
  - [x] Add all fields: fiscal_year_id, name, start_date, end_date, is_closed, is_locked, closed_at, closed_by_user_id, tenant_id
  - [x] Add indexes (start_date, end_date, is_closed, tenant_id)
  - [ ] Add unique constraint on date ranges per tenant (to be implemented via validation)
  - [ ] Add validation to prevent overlapping periods (to be done in model/service)

- [x] **Complete `exchange_rates` table migration**
  - [x] Add fields: from_currency_code, to_currency_code, rate, effective_date
  - [x] Add indexes (currency codes, effective_date)
  - [x] Add unique constraint on (from_currency_code, to_currency_code, effective_date)
  - [x] Use currency codes instead of foreign keys (no currencies table)

- [x] **Complete `account_balances` table migration** (Cache table)
  - [x] Add fields: account_id, fiscal_period_id, opening_balance, debit_total, credit_total, closing_balance, currency_code, tenant_id
  - [x] Add indexes (account_id, fiscal_period_id, tenant_id)
  - [x] Add unique constraint on (account_id, fiscal_period_id, tenant_id)

- [x] **Complete `tax_rates` table migration**
  - [x] Add fields: name, code, rate, is_compound, is_active, effective_from, effective_to
  - [x] Add indexes (code, is_active, effective_from/to)

- [x] **Create `account_types` table migration**
  - [x] Add fields: id, code, name, normal_balance, report_section, description, is_active
  - [ ] Seed with standard account types (seeder to be created)

- [x] **Create `fiscal_years` table migration**
  - [x] Add fields: name, start_date, end_date, is_closed, closed_at, closed_by_user_id, tenant_id
  - [x] Add indexes and constraints

- [x] **Create `transactions` table migration** (Optional general transactions table)
  - [x] Add fields: transaction_date, type, amount, description, status, reference, tenant_id
  - [x] Add indexes

### 2. Core Models & Eloquent Relationships ✓ (Completed)
- [x] **Account Model**
  - [x] Add fillable/guarded properties
  - [x] Add casts (is_active => boolean, etc.)
  - [x] Implement relationships: parent(), children(), accountType(), journalEntryItems(), balances()
  - [x] Add scopes: active(), byType(), byTenant(), systemAccounts(), manualEntryAllowed()
  - [x] Implement hierarchical methods: getFullCode(), getAncestors(), getDescendants(), isParentOf(), isChildOf(), isDescendantOf()
  - [x] Add validation rules

- [x] **AccountType Model**
  - [x] Add fillable/guarded properties
  - [x] Add casts (is_active => boolean)
  - [x] Implement relationships: accounts()
  - [x] Add scopes: active(), byCode()
  - [x] Add helper methods: isDebitType(), isCreditType()
  - [x] Add validation rules

- [x] **JournalEntry Model**
  - [x] Extend Eloquent Model properly
  - [x] Add fillable/guarded properties
  - [x] Add casts (transaction_date => date, posted_at => datetime, is_posted => boolean, etc.)
  - [x] Implement relationships: items(), postable(), fiscalPeriod(), createdBy(), postedBy(), voidedBy(), reversalOf(), reversals()
  - [x] Add scopes: posted(), unposted(), byPeriod(), byTenant(), notVoided(), notReversed(), byDateRange()
  - [x] Implement isBalanced(), canBePosted(), canBeVoided(), canBeReversed() methods
  - [x] Add auto-incrementing transaction_number generation in boot()
  - [x] Add validation rules

- [x] **JournalEntryItem Model**
  - [x] Extend Eloquent Model properly
  - [x] Add fillable/guarded properties
  - [x] Add casts (debit_amount => decimal, credit_amount => decimal)
  - [x] Implement relationships: journalEntry(), account(), dimension()
  - [x] Add scopes: debits(), credits(), byAccount(), byTenant()
  - [x] Implement getAmount(), getType(), isDebit(), isCredit() helper methods
  - [x] Add validation rules

- [x] **FiscalYear Model**
  - [x] Add fillable/guarded properties
  - [x] Add casts (start_date => date, end_date => date, is_closed => boolean)
  - [x] Implement relationships: fiscalPeriods(), closedBy()
  - [x] Add scopes: open(), closed(), current(), byTenant()
  - [x] Add helper methods: canBeClosed(), isActive(), getDurationInDays()
  - [x] Add validation rules

- [x] **FiscalPeriod Model**
  - [x] Add fillable/guarded properties
  - [x] Add casts (start_date => date, end_date => date, is_closed => boolean, is_locked => boolean)
  - [x] Implement relationships: fiscalYear(), journalEntries(), accountBalances(), closedBy()
  - [x] Add scopes: open(), closed(), locked(), unlocked(), current(), byTenant(), byYear()
  - [x] Add helper methods: canBeClosed(), canBeLocked(), canBeReopened(), isActive(), getDurationInDays(), hasPostedEntries()
  - [x] Add validation rules

- [x] **ExchangeRate Model**
  - [x] Add fillable/guarded properties
  - [x] Implement getRate($from, $to, $date) static method
  - [x] Implement convert($amount, $from, $to, $date) static method
  - [x] Add scopes: forCurrencyPair(), effectiveOn()
  - [x] Add helper methods: getInverseRate()
  - [x] Add validation rules

- [x] **AccountBalance Model**
  - [x] Add fillable/guarded properties
  - [x] Add casts (all amounts => decimal)
  - [x] Implement relationships: account(), fiscalPeriod()
  - [x] Add scopes: byAccount(), byPeriod(), byTenant()
  - [x] Add helper methods: calculateClosingBalance(), getNetMovement(), hasActivity()
  - [x] Add validation rules


### 3. Core Services Implementation
- [x] **JournalService**
  - [x] Create service class
  - [x] Implement createDraft(VoucherData $voucher): JournalEntry
  - [x] Implement validateBalance(JournalEntry $entry): bool
  - [x] Implement validatePeriod(JournalEntry $entry): bool
  - [x] Implement post(JournalEntry $entry): bool
  - [x] Implement reverse(JournalEntry $entry, ?string $reason): JournalEntry
  - [x] Add database transaction wrapping
  - [x] Add proper exception handling

- [x] **PostingService**
  - [x] Create service class
  - [x] Define all 20 transaction methods (stubs with documentation)
  - [x] Implement all 20 transaction methods (template-based)
  - [x] Each method should accept proper parameters and return JournalEntry
  - [x] Validate account existence and activity (via templates)
  - [x] Validate fiscal period is open (via JournalService)
  - [x] Ensure double-entry balance (via templates)
  - [x] Handle multi-currency transactions (via VoucherData)
  - [x] Add comprehensive validation (via JournalService)

- [x] **BalanceService**
  - [x] Create service class
  - [x] Implement getAccountBalance(Account $account, ?Carbon $asOfDate): float
  - [x] Implement getTrialBalance(?FiscalPeriod $period): Collection
  - [x] Implement getPeriodMovement(Account $account, FiscalPeriod $period): array
  - [x] Implement updateCachedBalances(FiscalPeriod $period): void
  - [x] Optimize queries for performance

- [x] **ReportService**
  - [x] Create service class
  - [x] Implement generate(string $reportType, array $params): Report
  - [x] Implement caching mechanism
  - [x] Add export formats support (array, JSON, CSV)

- [x] **PeriodService**
  - [x] Create service class
  - [x] Implement openPeriod(FiscalPeriod $period): bool
  - [x] Implement closePeriod(FiscalPeriod $period): bool
  - [x] Implement lockPeriod(FiscalPeriod $period): bool
  - [x] Implement rollForwardBalances(FiscalPeriod $from, FiscalPeriod $to): void
  - [x] Add validation and authorization checks

- [x] **AccountService** (Bonus - not in original roadmap)
  - [x] Create service class
  - [x] Implement CRUD operations (create, update, activate, deactivate)
  - [x] Implement account hierarchy management
  - [x] Implement search and filtering methods
  - [x] Add validation for account data

### 4. DTOs & Value Objects
- [x] **VoucherData DTO**
  - [x] Add properties: date, description, entries, reference, metadata, currency_code
  - [x] Implement constructor and validation
  - [x] Add fluent builder methods: on(), describe(), debit(), credit(), reference()
  - [x] Add toArray() method

- [x] **VoucherEntry DTO** (VoucherDataItem)
  - [x] Add properties: account_id, debit_amount, credit_amount, description, dimension
  - [x] Implement validation
  - [x] Add helper methods: isDebit(), isCredit(), getDebitAmount(), getCreditAmount()
  - [x] Add static factory methods: debit(), credit()

- [x] **BalanceData DTO**
  - [x] Add properties: opening, debits, credits, closing
  - [x] Add helper methods: calculateClosing(), getNetMovement(), getTotalActivity(), hasActivity()
  - [x] Add fluent builder methods and toArray()

### 5. Traits Implementation
- [ ] **HasUuid Trait**
  - [ ] Create trait for UUID support
  - [ ] Auto-generate UUIDs on model creation
  - [ ] Set $incrementing = false and $keyType = 'string'
  - [ ] Only apply when config('laravel_accounting.use_uuid') is true

- [x] **Postable Trait**
  - [x] Implement toVoucher(): VoucherData (abstract/interface method)
  - [x] Complete createJournal() implementation (stub exists)
  - [x] Complete postJournal() implementation (stub exists)
  - [x] Add journalEntry() morphOne relationship
  - [ ] Add validation before posting
  - [ ] Add event dispatching

- [x] **Reversible Trait**
  - [x] Create trait file (stub exists)
  - [ ] Implement reverse(?string $reason): JournalEntry
  - [ ] Add validation (can only reverse posted entries)
  - [ ] Create reversal entry with opposite debits/credits
  - [ ] Link original and reversal entries
  - [ ] Add event dispatching

- [x] **MultiTenant Trait**
  - [x] Create trait file (stub exists)
  - [ ] Implement global scope for tenant_id filtering
  - [ ] Add boot method to auto-set tenant_id
  - [ ] Add tenant() relationship if needed

- [x] **HasTablePrefix Trait**
  - [x] Ensure proper table name resolution from config

### 6. Contracts/Interfaces
- [x] **PostableModel Interface**
  - [x] Verify all methods are properly defined
  - [x] Add comprehensive PHPDoc

- [x] **PostableTemplate Interface**
  - [x] Complete interface definition
  - [x] Add methods: debit(), credit(), make(), validate()

### 7. Events System
- [ ] **Create Event Classes**
  - [ ] JournalEntryCreated
  - [ ] JournalEntryPosted
  - [ ] JournalEntryReversed
  - [ ] JournalEntryVoided
  - [ ] PeriodOpened
  - [ ] PeriodClosed
  - [ ] PeriodLocked
  - [ ] BalanceRecalculated

- [ ] **Dispatch Events**
  - [ ] Integrate events in JournalService
  - [ ] Integrate events in PeriodService
  - [ ] Add event listeners for audit trail

### 8. Exceptions
- [ ] **Create Custom Exceptions**
  - [ ] PeriodClosedException
  - [ ] PeriodLockedException
  - [ ] JournalNotBalancedException
  - [ ] AccountInactiveException
  - [ ] CurrencyMismatchException
  - [ ] InvalidAccountTypeException
  - [ ] DuplicatePostingException
  - [ ] InsufficientPermissionException

- [ ] **Implement Exception Handling**
  - [ ] Add try-catch blocks in services
  - [ ] Return meaningful error messages
  - [ ] Log exceptions appropriately

### 9. Reports Implementation
- [x] **BalanceSheet Report**
  - [x] Create report class (stub exists)
  - [ ] Implement generate() method
  - [ ] Group accounts by type (Assets, Liabilities, Equity)
  - [ ] Calculate totals and subtotals
  - [ ] Support as-of-date parameter
  - [ ] Return structured data array

- [x] **IncomeStatement Report**
  - [x] Create report class (stub exists)
  - [ ] Implement generate() method
  - [ ] Group accounts (Revenue, Expenses)
  - [ ] Calculate net income
  - [ ] Support date range parameter
  - [ ] Return structured data array

- [ ] **CashFlowStatement Report**
  - [ ] Implement generate() method (indirect method minimum)
  - [ ] Categorize: Operating, Investing, Financing activities
  - [ ] Support date range parameter

- [ ] **TrialBalance Report**
  - [ ] Implement generate() method
  - [ ] List all accounts with debit/credit balances
  - [ ] Verify debits = credits
  - [ ] Support as-of-date parameter

- [ ] **GeneralLedger Report**
  - [ ] Implement generate() method
  - [ ] Show all journal entries chronologically
  - [ ] Support date range and account filters
  - [ ] Include running balances

- [ ] **AccountLedger Report**
  - [ ] Implement generate() method
  - [ ] Show transactions for specific account
  - [ ] Include running balance
  - [ ] Support date range parameter

### 10. Postable Templates ✓ (Completed)
- [x] **Create Template Base Class**
  - [x] Implement PostableTemplate interface
  - [x] Add common validation logic
  - [x] Add fluent builder pattern

- [x] **Invoice Template** (CreditSaleTemplate, CashSaleTemplate)
  - [x] Debit: Accounts Receivable
  - [x] Credit: Sales Revenue
  - [x] Credit: Sales Tax Payable (optional)
  - [ ] Add validation rules

- [x] **Purchase Template** (PurchaseOnAccountTemplate, CashPurchaseTemplate)
  - [x] Debit: Inventory/Expense
  - [x] Debit: Input VAT (optional)
  - [x] Credit: Accounts Payable
  - [ ] Add validation rules

- [x] **Expense Template** (PrepaidExpenseTemplate)
  - [x] Debit: Expense Account
  - [x] Credit: Cash/Payable
  - [ ] Add validation rules

- [x] **Payment Template** (PaymentReceiptTemplate, BillPaymentTemplate)
  - [x] Debit: Payable/Receivable
  - [x] Credit: Cash/Bank
  - [ ] Add validation rules

- [x] **Adjustment Template** (InventoryAdjustmentTemplate)
  - [x] Flexible debit/credit configuration
  - [ ] Add validation rules

- [x] **Additional Templates Implemented**
  - [x] PayrollAccrualTemplate
  - [x] PayrollPaymentTemplate
  - [x] DepreciationTemplate
  - [x] FixedAssetPurchaseTemplate
  - [x] SalesReturnTemplate
  - [x] PurchaseReturnTemplate
  - [x] AccruedRevenueTemplate
  - [x] LoanReceiptTemplate
  - [x] LoanPaymentTemplate
  - [x] OwnerInvestmentTemplate
  - [x] DividendTemplate
  - [x] BadDebtTemplate
  - [x] TemplateRegistry for managing all templates

### 11. Artisan Commands
- [x] **InstallCommand**
  - [x] Add UUID vs ID choice prompt
  - [x] Update config file with user's UUID preference
  - [x] Complete implementation
  - [x] Publish config
  - [x] Publish migrations
  - [x] Run migrations (optional)
  - [x] Seed default data (account types, currencies)

- [ ] **ClosePeriodCommand**
  - [ ] Accept period ID or date range
  - [ ] Validate all entries are posted
  - [ ] Calculate and cache balances
  - [ ] Mark period as closed
  - [ ] Add confirmation prompt

- [ ] **UpdateBalancesCommand**
  - [ ] Accept period or account parameters
  - [ ] Recalculate balances from journal entries
  - [ ] Update account_balances cache table
  - [ ] Add progress bar for large datasets

- [ ] **CreateFiscalPeriodCommand**
  - [ ] Accept year and period type (monthly/quarterly/yearly)
  - [ ] Generate periods automatically
  - [ ] Validate no overlaps

### 12. Configuration ✓ (Completed)
- [x] **Review and enhance config file**
  - [x] Add use_uuid option (true/false) - for UUID vs auto-increment IDs
  - [x] Add base_currency option (default_currency_code)
  - [x] Add default_fiscal_year_start (month/day)
  - [x] Add auto_post option (true/false)
  - [x] Add balance_cache_enabled option
  - [x] Add report_cache_ttl option
  - [x] Add supported_currencies array
  - [x] Add account_code_format (regex pattern)

### 13. Seeders
- [x] **AccountTypeSeeder**
  - [x] Create seeder file (stub exists)
  - [ ] Implement AccountTypeSeeder

- [ ] **DefaultChartOfAccountsSeeder** (Optional)
  - [ ] Seed basic COA structure (ChartOfAccountsSeeder)
  - [ ] Make it optional/configurable

### 14. Validation & Business Rules
- [ ] **Implement Validation Rules**
  - [ ] Journal entry must balance (debits = credits)
  - [ ] Cannot post to closed/locked periods
  - [ ] Cannot post to inactive accounts
  - [ ] Cannot delete posted journal entries
  - [ ] Cannot modify posted journal entries (only reverse)
  - [ ] Account code must follow configured format
  - [ ] Transaction date must fall within fiscal period
  - [ ] Currency codes must be valid ISO 4217

- [ ] **Add Model Observers**
  - [ ] JournalEntryObserver (prevent deletion if posted)
  - [ ] AccountObserver (prevent deletion if has transactions)
  - [ ] FiscalPeriodObserver (validate dates)

### 15. Testing
- [ ] **Unit Tests**
  - [ ] Test all service methods
  - [ ] Test all model methods and relationships
  - [ ] Test DTOs and value objects
  - [ ] Test validation rules
  - [ ] Test exception handling
  - [ ] Target: 80%+ code coverage

- [ ] **Feature Tests**
  - [ ] Test complete posting workflow
  - [ ] Test reversal workflow
  - [ ] Test period closing workflow
  - [ ] Test multi-currency transactions
  - [ ] Test multi-tenancy isolation
  - [ ] Test report generation

- [ ] **Integration Tests**
  - [ ] Test with sample business scenarios
  - [ ] Test all 20 posting service methods
  - [ ] Test all reports with real data

### 16. Documentation
- [ ] **Update README.md**
  - [ ] Add comprehensive installation instructions
  - [ ] Add configuration guide
  - [ ] Add usage examples for each feature
  - [ ] Add architecture overview diagram
  - [ ] Add FAQ section
  - [ ] Add troubleshooting guide

- [ ] **API Documentation**
  - [ ] Document all public methods with PHPDoc
  - [ ] Add @param and @return types
  - [ ] Add @throws documentation
  - [ ] Add usage examples in docblocks

- [ ] **Create UPGRADE.md**
  - [ ] Document breaking changes
  - [ ] Provide migration guides

- [ ] **Create CONTRIBUTING.md**
  - [ ] Add contribution guidelines
  - [ ] Add code style requirements
  - [ ] Add PR template

### 17. Code Quality & Standards
- [ ] **Run Laravel Pint**
  - [ ] Fix all code style issues
  - [ ] Configure pint.json if needed

- [ ] **Run PHPStan**
  - [ ] Fix all level 5+ errors
  - [ ] Update phpstan-baseline.neon
  - [ ] Target: Level 8 compliance

- [ ] **Architecture Tests (Pest)**
  - [ ] Ensure models extend Eloquent
  - [ ] Ensure services don't use facades
  - [ ] Ensure proper namespace organization
  - [ ] Ensure no debugging code (dd, dump, ray)

### 18. Performance Optimization
- [ ] **Database Optimization**
  - [ ] Add all necessary indexes
  - [ ] Implement eager loading where needed
  - [ ] Add database query logging in tests
  - [ ] Optimize N+1 queries

- [ ] **Caching Strategy**
  - [ ] Implement balance caching
  - [ ] Implement report caching
  - [ ] Add cache invalidation logic
  - [ ] Add cache warming commands

### 19. Security
- [ ] **Multi-tenancy Security**
  - [ ] Ensure global scopes are applied
  - [ ] Test tenant isolation thoroughly
  - [ ] Add tenant_id to all relevant tables

- [ ] **Authorization**
  - [ ] Consider adding policy classes
  - [ ] Document required permissions
  - [ ] Add authorization checks in services

- [ ] **Input Validation**
  - [ ] Sanitize all user inputs
  - [ ] Validate all amounts (no negative values where inappropriate)
  - [ ] Validate date ranges

### 20. Package Polish
- [ ] **Service Provider**
  - [ ] Register all services in container
  - [ ] Register facades
  - [ ] Load routes (if any)
  - [ ] Load views (if any)
  - [ ] Publish assets

- [ ] **Changelog**
  - [ ] Document all changes
  - [ ] Follow Keep a Changelog format
  - [ ] Add version numbers

- [ ] **License**
  - [ ] Verify MIT license is appropriate
  - [ ] Update copyright year

- [ ] **Composer.json**
  - [ ] Verify all dependencies
  - [ ] Add proper version constraints
  - [ ] Update package description
  - [ ] Add keywords for discoverability

### 21. Pre-Release Checklist
- [ ] All migrations are complete and tested
- [ ] All models have proper relationships
- [ ] All services are fully implemented
- [ ] All reports generate correct data
- [ ] All tests pass (100% success rate)
- [ ] Code coverage >= 80%
- [ ] PHPStan level 8 passes
- [ ] Laravel Pint passes
- [ ] Documentation is complete
- [ ] README has clear examples
- [ ] CHANGELOG is up to date
- [ ] No TODO comments in production code
- [ ] Package works in fresh Laravel installation
- [ ] Multi-currency support is functional
- [ ] Multi-tenancy is properly isolated
- [ ] Performance benchmarks are acceptable

---

## UUID vs Auto-Increment ID Implementation Guide

### Overview
The package supports both **auto-incrementing integer IDs** (default) and **UUIDs** for primary keys. Users can choose their preference during installation via the `laravel-accounting:install` command.

### Installation Options

**Interactive Installation:**
```bash
php artisan laravel-accounting:install
```
The command will prompt: "Do you want to use UUIDs instead of auto-incrementing IDs for primary keys?"

**Non-Interactive Installation:**
```bash
# Use UUIDs
php artisan laravel-accounting:install --uuid

# Use auto-incrementing IDs (default)
php artisan laravel-accounting:install
```

### Configuration

The choice is stored in `config/laravel_accounting.php`:
```php
'use_uuid' => false, // or true for UUIDs
```

### Implementation Details

#### 1. Migration Helper Method
Create a helper method in a base migration class or trait:
```php
protected function addPrimaryKey(Blueprint $table, string $column = 'id'): void
{
    if (config('laravel_accounting.use_uuid', false)) {
        $table->uuid($column)->primary();
    } else {
        $table->id($column);
    }
}

protected function addForeignKey(Blueprint $table, string $column, string $references, string $on): void
{
    if (config('laravel_accounting.use_uuid', false)) {
        $table->uuid($column);
    } else {
        $table->unsignedBigInteger($column);
    }
    $table->foreign($column)->references($references)->on($on);
}
```

#### 2. HasUuid Trait
Create `src/Traits/HasUuid.php`:
```php
namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
```

#### 3. Model Implementation
Models should conditionally use the HasUuid trait:
```php
class Account extends Model
{
    use HasTablePrefix, MultiTenant;

    public function __construct(array $attributes = [])
    {
        if (config('laravel_accounting.use_uuid', false)) {
            $this->usesUuid();
        }
        parent::__construct($attributes);
    }

    protected function usesUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    protected static function boot()
    {
        parent::boot();
        
        if (config('laravel_accounting.use_uuid', false)) {
            static::creating(function ($model) {
                if (empty($model->{$model->getKeyName()})) {
                    $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
                }
            });
        }
    }
}
```

#### 4. Migration Example
```php
public function up()
{
    Schema::create(config('laravel_accounting.table_prefix', 'la').'_accounts', function (Blueprint $table) {
        // Primary key - dynamic based on config
        if (config('laravel_accounting.use_uuid', false)) {
            $table->uuid('id')->primary();
        } else {
            $table->id();
        }

        $table->string('code')->unique();
        $table->string('name');
        
        // Foreign key - dynamic based on config
        if (config('laravel_accounting.use_uuid', false)) {
            $table->uuid('account_type_id')->nullable();
            $table->uuid('parent_id')->nullable();
        } else {
            $table->unsignedBigInteger('account_type_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
        }
        
        $table->foreign('account_type_id')->references('id')->on(config('laravel_accounting.table_prefix', 'la').'_account_types');
        $table->foreign('parent_id')->references('id')->on(config('laravel_accounting.table_prefix', 'la').'_accounts');
        
        $table->timestamps();
    });
}
```

### Important Considerations

1. **One-Time Choice**: The UUID vs ID choice should be made during initial installation. Switching between them after data exists requires complex migrations.

2. **Performance**: 
   - Auto-increment IDs: Faster inserts, smaller index size, better for high-volume transactions
   - UUIDs: Better for distributed systems, no collision risk, can be generated client-side

3. **Foreign Keys**: All foreign key columns must match the primary key type (UUID or bigInteger).

4. **Polymorphic Relations**: The `postable_id` in journal_entries must be a string when using UUIDs to accommodate both UUID and integer foreign models.

5. **Testing**: Ensure all tests work with both UUID and auto-increment configurations.

### Checklist for UUID Support
- [x] Add `use_uuid` config option
- [x] Update InstallCommand with UUID prompt
- [x] Update InstallCommand to modify config based on user choice
- [ ] Create HasUuid trait
- [ ] Create migration helper methods for dynamic ID types
- [ ] Update all migration files to support both ID types
- [ ] Update all models to conditionally use UUID trait
- [ ] Test all functionality with UUIDs enabled
- [ ] Test all functionality with auto-increment IDs (default)
- [ ] Document UUID vs ID trade-offs in README
- [ ] Add migration guide for switching between types (advanced)

---

**MVP Definition:** A functional, stable, production-ready accounting package that can:
1. Manage chart of accounts with hierarchical structure
2. Create and post double-entry journal entries
3. Handle multi-currency transactions
4. Manage fiscal periods (open/close/lock)
5. Generate core financial reports (Balance Sheet, Income Statement, Trial Balance, General Ledger)
6. Support multi-tenancy
7. Provide postable trait for business models
8. Include 5 standard posting templates
9. Maintain immutable accounting records
10. Pass comprehensive test suite with 80%+ coverage
