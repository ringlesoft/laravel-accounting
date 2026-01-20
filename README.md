# Laravel Accounting
## Overview
Laravel Accounting Core is a framework-agnostic accounting engine designed to abstract all fundamental accounting operations while remaining fully flexible and developer-driven.
It provides a journal-centric, double-entry accounting kernel that can be integrated into any Laravel application via traits, contracts, and facades.
The package does not model business documents (e.g. invoices, payments) directly. Instead, it enables application models to declare themselves as postable accounting events, which are transformed into standardized vouchers and posted into the accounting ledger.

## Core Design Principles
- Double-entry, journal-first accounting
- Immutable accounting records
- Strict separation of business logic and accounting logic
- Extensibility through traits, contracts, and templates
- Report data generation without presentation concerns
