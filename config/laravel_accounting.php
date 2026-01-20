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
     * Time-to-live for report cache in seconds
     * Set to 0 to disable report caching
     * Default: 3600 (1 hour)
     */
    'report_cache_ttl' => 3600,

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

];
