<?php

use RingleSoft\LaravelAccounting\Support\AuthUserTenantResolver;

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

    /*
     * Class used to resolve the current tenant. Must implement
     * RingleSoft\LaravelAccounting\Contracts\TenantResolver. The default reads
     * `multi_tenancy_field` from the authenticated user; swap it for one that
     * reads your app's tenant context so code running with no logged-in user
     * (queued jobs, scheduled commands, seeders, tenant provisioning) is still
     * scoped to and attributed to the right tenant.
     */
    'tenant_resolver' => AuthUserTenantResolver::class,

    /*
     * When `true`, every multi-tenant model must belong to exactly
     * one resolvable tenant: querying as an authenticated tenant never
     * returns a row with a null tenant_id, and creating a record with no
     * resolvable tenant_id (no authenticated user, and no tenant_id set
     * explicitly) throws a TenantRequiredException instead of silently
     * leaving it null.
     *
     * `false` (default): allow "global"/shared records alongside tenant-owned
     * ones: rows with a null tenant_id become visible to every tenant, and
     * may be created without a resolvable tenant (e.g. from a console
     * command or seeder) without being rejected.
     */
    'strict_multi_tenancy_check' => false,

    /*
     * Your own account preset enums (string-backed, implementing
     * RingleSoft\LaravelAccounting\Contracts\AccountPresetContract).
     * Presets work in templates without being listed here; listing them lets
     * CustomPresetSeeder seed the accounts their #[PresetAccount] cases
     * describe, and checks their values don't collide with other presets.
     * e.g. [App\Accounting\MyPreset::class]
     */
    'custom_presets' => [],

    /*
     * The chart of accounts structure. When `true` (default):
     * - every account must have a parent - the root (first-level) accounts
     *   come from the package's chart only;
     * - those root accounts can't be changed or deleted;
     * - an account's code is generated under its parent when omitted, and an
     *   explicit code must fit under the parent (see AccountCodeGenerator).
     * Codes are still generated when omitted if this is `false`.
     */
    'enforce_chart_structure' => true,

    /*
     * Accounts of the package's chart to leave out when seeding, with their
     * whole branch, e.g. [AccountPreset::DIGITAL_ASSETS->value]. Root accounts
     * can't be excluded.
     */
    'excluded_presets' => [],

    /*
     * Replace the name, code or description of accounts of the package's
     * chart when seeding, e.g.
     * [AccountPreset::CASH->value => ['name' => 'Cash on Hand']].
     * Root accounts can't be overridden.
     */
    'preset_overrides' => [],

    /*
     *
     */
    'default_currency_code' => 'USD',

    /*
     *
     */
    'date_format' => 'Y-m-d',

    /*
     * Prefix used when auto-generating journal entry transaction numbers
     * (e.g. "JE-20260101-000001")
     */
    'transaction_number_prefix' => 'JE',

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
     * "Accounting Basis: Accrual vs Cash" in docs/templates-api.md.
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
