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
