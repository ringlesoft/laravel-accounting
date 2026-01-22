<?php

namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelAccounting\Scopes\MultiTenantScope;

trait MultiTenant
{
    protected static function bootMultiTenant(): void
    {
        static::addGlobalScope(new MultiTenantScope);

        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id')) {
                $tenantField = config('laravel_accounting.multi_tenancy_field', 'tenant_id');
                $tenantId = Auth::user()?->{$tenantField};

                if ($tenantId) {
                    $model->setAttribute('tenant_id', $tenantId);
                }
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
