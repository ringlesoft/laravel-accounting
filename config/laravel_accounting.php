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

];
