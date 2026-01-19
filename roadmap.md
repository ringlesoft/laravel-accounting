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

### 1. Core Database Schema & Migrations ✓ (In Progress)
- [ ] **Complete `accounts` table migration**
  - [ ] Add all fields: code, name, account_type_id, category, sub_category, report_section, parent_id, currency_id, is_active, is_system_account, allows_manual_entries, tenant_id
  - [ ] Add proper indexes (code, parent_id, tenant_id, is_active)
  - [ ] Add foreign key constraints
  - [ ] Add unique constraint on (code, tenant_id)

- [ ] **Complete `journal_entries` table migration**
  - [ ] Add all fields: transaction_number, transaction_date, posted_at, description, postable_id, postable_type, reference, currency_code, exchange_rate, fiscal_period_id, created_by_user_id, posted_by_user_id, is_voided, voided_at, voided_by_user_id, is_posted, is_reversed, reversal_of_id, tenant_id
  - [ ] Add indexes (transaction_number, transaction_date, fiscal_period_id, postable_type/id, tenant_id)
  - [ ] Add foreign key constraints
  - [ ] Implement auto-incrementing transaction_number logic

- [ ] **Complete `journal_entry_items` table migration**
  - [ ] Add all fields: journal_entry_id, account_id, description, debit_amount, credit_amount, currency_code, exchange_rate, dimension_id, dimension_type, tenant_id
  - [ ] Add indexes (journal_entry_id, account_id, tenant_id)
  - [ ] Add foreign key constraints with cascade rules
  - [ ] Add check constraint (debit_amount >= 0 AND credit_amount >= 0)

- [ ] **Complete `fiscal_periods` table migration**
  - [ ] Add all fields: fiscal_year_id, name, start_date, end_date, is_closed, is_locked, closed_at, closed_by_user_id, tenant_id
  - [ ] Add indexes (start_date, end_date, is_closed, tenant_id)
  - [ ] Add unique constraint on date ranges per tenant
  - [ ] Add validation to prevent overlapping periods

- [ ] **Complete `currencies` table migration**
  - [ ] Add fields: code (ISO 4217), name, symbol, decimal_places, is_active
  - [ ] Seed with common currencies
  - [ ] Add unique constraint on code

- [ ] **Complete `exchange_rates` table migration**
  - [ ] Verify fields: from_currency_code, to_currency_code, rate, effective_date
  - [ ] Add indexes (from/to currency codes, effective_date)
  - [ ] Add unique constraint on (from_currency, to_currency, effective_date)

- [ ] **Complete `account_balances` table migration** (Cache table)
  - [ ] Add fields: account_id, fiscal_period_id, opening_balance, debit_total, credit_total, closing_balance, currency_code, tenant_id
  - [ ] Add indexes (account_id, fiscal_period_id, tenant_id)
  - [ ] Add unique constraint on (account_id, fiscal_period_id, tenant_id)

- [ ] **Complete `tax_rates` table migration**
  - [ ] Verify fields: name, code, rate, is_compound, is_active, effective_from, effective_to
  - [ ] Add indexes (code, is_active, effective_from/to)

- [ ] **Create `account_types` table migration**
  - [ ] Add fields: id, code (ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE), name, normal_balance (DEBIT/CREDIT)
  - [ ] Seed with standard account types

- [ ] **Create `fiscal_years` table migration**
  - [ ] Add fields: name, start_date, end_date, is_closed, tenant_id
  - [ ] Add indexes and constraints

### 2. Core Models & Eloquent Relationships
- [ ] **Account Model**
  - [ ] Add fillable/guarded properties
  - [ ] Add casts (is_active => boolean, etc.)
  - [ ] Implement relationships: parent(), children(), accountType(), journalEntryItems(), balances()
  - [ ] Add scopes: active(), byType(), byTenant()
  - [ ] Implement hierarchical methods: getFullCode(), getAncestors(), getDescendants()
  - [ ] Add validation rules

- [ ] **JournalEntry Model**
  - [ ] Extend Eloquent Model properly
  - [ ] Add fillable/guarded properties
  - [ ] Add casts (transaction_date => date, posted_at => datetime, is_posted => boolean, etc.)
  - [ ] Implement relationships: items(), postable(), fiscalPeriod(), createdBy(), postedBy(), reversalOf(), reversals()
  - [ ] Add scopes: posted(), unposted(), byPeriod(), byTenant()
  - [ ] Implement isBalanced() method
  - [ ] Add UUID or auto-increment for transaction_number

- [ ] **JournalEntryItem Model**
  - [ ] Extend Eloquent Model properly
  - [ ] Add fillable/guarded properties
  - [ ] Add casts (debit_amount => decimal, credit_amount => decimal)
  - [ ] Implement relationships: journalEntry(), account(), dimension()
  - [ ] Add scopes: debits(), credits(), byAccount()
  - [ ] Implement getAmount() and getType() helper methods

- [ ] **FiscalPeriod Model**
  - [ ] Add fillable/guarded properties
  - [ ] Add casts (start_date => date, end_date => date, is_closed => boolean, is_locked => boolean)
  - [ ] Implement relationships: fiscalYear(), journalEntries(), accountBalances()
  - [ ] Add scopes: open(), closed(), current(), byTenant()
  - [ ] Implement validation: no overlapping periods, start < end

- [ ] **Currency Model**
  - [ ] Add fillable/guarded properties
  - [ ] Implement relationships: exchangeRates()
  - [ ] Add helper methods: format($amount)

- [ ] **ExchangeRate Model**
  - [ ] Add fillable/guarded properties
  - [ ] Implement getRate($from, $to, $date) static method
  - [ ] Add validation rules

### 3. Core Services Implementation
- [ ] **JournalService**
  - [ ] Create service class
  - [ ] Implement createDraft(VoucherData $voucher): JournalEntry
  - [ ] Implement validateBalance(JournalEntry $entry): bool
  - [ ] Implement validatePeriod(JournalEntry $entry): bool
  - [ ] Implement post(JournalEntry $entry): bool
  - [ ] Implement reverse(JournalEntry $entry, ?string $reason): JournalEntry
  - [ ] Add database transaction wrapping
  - [ ] Add proper exception handling

- [ ] **PostingService**
  - [ ] Implement all 20 transaction methods (currently empty stubs)
  - [ ] Each method should accept proper parameters and return JournalEntry
  - [ ] Validate account existence and activity
  - [ ] Validate fiscal period is open
  - [ ] Ensure double-entry balance
  - [ ] Handle multi-currency transactions
  - [ ] Add comprehensive validation

- [ ] **BalanceService**
  - [ ] Create service class
  - [ ] Implement getAccountBalance(Account $account, ?Carbon $asOfDate): float
  - [ ] Implement getTrialBalance(?FiscalPeriod $period): Collection
  - [ ] Implement getPeriodMovement(Account $account, FiscalPeriod $period): array
  - [ ] Implement updateCachedBalances(FiscalPeriod $period): void
  - [ ] Optimize queries for performance

- [ ] **ReportService**
  - [ ] Create service class
  - [ ] Implement generate(string $reportType, array $params): Report
  - [ ] Implement caching mechanism
  - [ ] Add export formats support (array, JSON)

- [ ] **PeriodService**
  - [ ] Create service class
  - [ ] Implement openPeriod(FiscalPeriod $period): bool
  - [ ] Implement closePeriod(FiscalPeriod $period): bool
  - [ ] Implement lockPeriod(FiscalPeriod $period): bool
  - [ ] Implement rollForwardBalances(FiscalPeriod $from, FiscalPeriod $to): void
  - [ ] Add validation and authorization checks

### 4. DTOs & Value Objects
- [ ] **VoucherData DTO**
  - [ ] Add properties: date, description, entries, reference, metadata, currency_code
  - [ ] Implement constructor and validation
  - [ ] Add fluent builder methods: on(), describe(), debit(), credit(), reference()
  - [ ] Add toArray() method

- [ ] **VoucherEntry DTO**
  - [ ] Add properties: account_id, debit_amount, credit_amount, description, dimension
  - [ ] Implement validation

- [ ] **BalanceData DTO**
  - [ ] Add properties: opening, debits, credits, closing
  - [ ] Add helper methods

### 5. Traits Implementation
- [ ] **Postable Trait**
  - [ ] Implement toVoucher(): VoucherData (abstract/interface method)
  - [ ] Complete createJournal() implementation
  - [ ] Complete postJournal() implementation
  - [ ] Add journalEntry() morphOne relationship
  - [ ] Add validation before posting
  - [ ] Add event dispatching

- [ ] **Reversible Trait**
  - [ ] Implement reverse(?string $reason): JournalEntry
  - [ ] Add validation (can only reverse posted entries)
  - [ ] Create reversal entry with opposite debits/credits
  - [ ] Link original and reversal entries
  - [ ] Add event dispatching

- [ ] **MultiTenant Trait**
  - [ ] Implement global scope for tenant_id filtering
  - [ ] Add boot method to auto-set tenant_id
  - [ ] Add tenant() relationship if needed

- [ ] **HasTablePrefix Trait**
  - [ ] Ensure proper table name resolution from config

### 6. Contracts/Interfaces
- [ ] **PostableModel Interface**
  - [ ] Verify all methods are properly defined
  - [ ] Add comprehensive PHPDoc

- [ ] **PostableTemplate Interface**
  - [ ] Complete interface definition
  - [ ] Add methods: debit(), credit(), make(), validate()

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
- [ ] **BalanceSheet Report**
  - [ ] Implement generate() method
  - [ ] Group accounts by type (Assets, Liabilities, Equity)
  - [ ] Calculate totals and subtotals
  - [ ] Support as-of-date parameter
  - [ ] Return structured data array

- [ ] **IncomeStatement Report**
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

### 10. Postable Templates
- [ ] **Create Template Base Class**
  - [ ] Implement PostableTemplate interface
  - [ ] Add common validation logic
  - [ ] Add fluent builder pattern

- [ ] **Invoice Template**
  - [ ] Debit: Accounts Receivable
  - [ ] Credit: Sales Revenue
  - [ ] Credit: Sales Tax Payable (optional)
  - [ ] Add validation rules

- [ ] **Purchase Template**
  - [ ] Debit: Inventory/Expense
  - [ ] Debit: Input VAT (optional)
  - [ ] Credit: Accounts Payable
  - [ ] Add validation rules

- [ ] **Expense Template**
  - [ ] Debit: Expense Account
  - [ ] Credit: Cash/Payable
  - [ ] Add validation rules

- [ ] **Payment Template**
  - [ ] Debit: Payable/Receivable
  - [ ] Credit: Cash/Bank
  - [ ] Add validation rules

- [ ] **Adjustment Template**
  - [ ] Flexible debit/credit configuration
  - [ ] Add validation rules

### 11. Artisan Commands
- [ ] **InstallCommand**
  - [ ] Complete implementation
  - [ ] Publish config
  - [ ] Publish migrations
  - [ ] Run migrations (optional)
  - [ ] Seed default data (account types, currencies)

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

### 12. Configuration
- [ ] **Review and enhance config file**
  - [ ] Add base_currency option
  - [ ] Add default_fiscal_year_start (month/day)
  - [ ] Add auto_post option (true/false)
  - [ ] Add balance_cache_enabled option
  - [ ] Add report_cache_ttl option
  - [ ] Add supported_currencies array
  - [ ] Add account_code_format (regex pattern)

### 13. Seeders
- [ ] **AccountTypeSeeder**
  - [ ] Seed 5 standard account types with normal balances

- [ ] **CurrencySeeder**
  - [ ] Seed major world currencies (USD, EUR, GBP, etc.)

- [ ] **DefaultChartOfAccountsSeeder** (Optional)
  - [ ] Seed basic COA structure
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
