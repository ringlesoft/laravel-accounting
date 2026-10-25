<?php

namespace RingleSoft\LaravelAccounting\Contracts;

/**
 * Resolves the tenant that the package's multi-tenant models are scoped to
 * and attributed to. Bind your own implementation through the
 * `laravel_accounting.tenant_resolver` config key when the current tenant
 * comes from somewhere other than the authenticated user (e.g. a tenancy
 * package's context, so queued jobs, scheduled commands and seeders work).
 */
interface TenantResolver
{
    /**
     * The current tenant's id, or null when there is no tenant context.
     */
    public function resolve(): string|int|null;
}
