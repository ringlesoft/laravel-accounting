<?php

namespace RingleSoft\LaravelAccounting\Support;

use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;

/**
 * Default resolver: the authenticated user's `multi_tenancy_field`.
 */
class AuthUserTenantResolver implements TenantResolver
{
    public function resolve(): string|int|null
    {
        $tenantField = config('laravel_accounting.multi_tenancy_field', 'tenant_id');

        return Auth::user()?->{$tenantField};
    }
}
