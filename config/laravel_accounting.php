<?php

// config for RingleSoft/LaravelAccounting
return [
    /*
     * The prefix used for tables used by the package
     */
    'table_prefix' => 'la',

    /**
     * The name of the multi tenancy field in the users table
     */
    'multi_tenancy_field' => 'tenant_id',

    'default_currency_code' => 'USD',
    'date_format' => 'Y-m-d',

    /*
     * Use UUIDs instead of auto-incrementing IDs for primary keys
     * This can be set during installation via the laravel-accounting:install command
     */
    'use_uuid' => false,

    /*
     * Default fiscal year start date (month and day)
     * Format: ['month' => 1, 'day' => 1] for January 1st
     */
    'default_fiscal_year_start' => [
        'month' => 1,
        'day' => 1,
    ],

    /**
     * The number of fiscal periods within a fiscal year
     * Common values: 12 (monthly), 4 (quarterly), 2 (semi-annual), 1 (annual)
     */
    'fiscal_periods_count' => 2,

    /*
     * Whether this installation runs cash-basis accounting instead of accrual
     * (the default, and the only GAAP/IFRS-compliant basis above small-business
     * revenue thresholds). This flag is informational only - it is not read or
     * enforced anywhere in the package yet. Revenue/expense timing is determined
     * entirely by which templates you post with: accrual-style templates
     * (CreditSaleTemplate, PurchaseOnAccountTemplate, PayrollAccrualTemplate,
     * UnearnedRevenueTemplate, AccruedRevenueTemplate, PrepaidExpenseTemplate, ...)
     * use timing accounts like Accounts Receivable/Payable; cash-style templates
     * (CashSaleTemplate, CashPurchaseTemplate, PayrollExpenseTemplate,
     * PrepaidExpensePaymentTemplate, ...) post straight to Cash. Setting this to
     * true does NOT prevent accrual-only templates from being used - see
     * "Accounting Basis: Accrual vs Cash" in docs/available-templates.md.
     */
    'cash_basis' => false,

    /*
     * Automatically post journal entries when created
     * If false, entries will be created in draft status and require manual posting
     */
    'auto_post' => false,

    /*
     * Enable caching of account balances for performance
     * When enabled, balances are stored in the account_balances table
     */
    'balance_cache_enabled' => true,

    /*
     * Enable caching of generated reports (trial balance, balance sheet, etc.)
     */
    'report_cache_enabled' => true,

    /*
     * Time-to-live for report cache in seconds
     * Set to 0 to disable report caching
     * Default: 3600 (1 hour)
     */
    'report_cache_ttl' => 3600,

    /*
     * Prefix used for this package's own cache entries (report cache and its key
     * index). Keeping it distinct from the host app's cache keys lets us clear
     * only this package's cache without touching unrelated application cache.
     */
    'cache_prefix' => 'laravel_accounting',

    /*
     * List of supported currency codes (ISO 4217)
     * Leave empty to allow all currencies
     */
    'supported_currencies' => [],

    /*
     * Account code format validation (regex pattern)
     * Default pattern allows hierarchical codes like: 1000, 1001, 1001-01, etc.
     * Set to null to disable format validation
     */
    'account_code_format' => '/^[0-9]{4}(-[0-9]{2})?$/',

    /*
     * Allow posting journal entries to parent accounts
     * If false, posting to accounts that have children will throw an exception
     * Default: false
     */
    'allow_posting_on_parents' => false,

];
