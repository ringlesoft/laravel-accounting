<?php

namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;
use RingleSoft\LaravelAccounting\Exceptions\TenantRequiredException;
use RingleSoft\LaravelAccounting\Scopes\MultiTenantScope;

trait MultiTenant
{
    protected static function bootMultiTenant(): void
    {
        static::addGlobalScope(new MultiTenantScope);

        static::creating(function (Model $model) {
            $tenantField = config('laravel_accounting.multi_tenancy_field', 'tenant_id');
            $tenantId = app(TenantResolver::class)->resolve();

            // Always defer to the resolved tenant when one exists - never to
            // a caller-supplied tenant_id (e.g. via mass assignment), which
            // would otherwise let a user of one tenant attribute data to
            // another.
            if ($tenantId) {
                $model->setAttribute('tenant_id', $tenantId);

                return;
            }

            // No resolvable tenant: a model's tenant_id is taken as given
            // (CLI, queued jobs, seeders) - but under the default strict
            // check, it must still resolve to something. Set
            // strict_multi_tenancy_check to false to allow a genuinely
            // global/shared record to be created with no tenant at all.
            if (config('laravel_accounting.strict_multi_tenancy_check', true) && ! $model->getAttribute('tenant_id')) {
                throw new TenantRequiredException(
                    'Cannot create '.get_class($model)." without a resolvable tenant_id. Authenticate as a user with a '{$tenantField}' (or configure a tenant_resolver), set tenant_id explicitly, or set strict_multi_tenancy_check to false to allow global/shared records."
                );
            }
        });
    }

    public function getTenantId()
    {
        return $this->getAttribute('tenant_id');
    }

    public function setTenantId($tenantId): void
    {
        $this->setAttribute('tenant_id', $tenantId);
    }
}
