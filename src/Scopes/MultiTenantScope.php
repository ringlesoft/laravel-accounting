<?php

namespace RingleSoft\LaravelAccounting\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;

class MultiTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantResolver::class)->resolve();

        // With no resolvable tenant (e.g. CLI, queued jobs or seeders under the
        // default auth-based resolver) there is no tenant to scope to - the caller is responsible for filtering
        // itself, mirroring MultiTenant::bootMultiTenant()'s write-side design.
        if (! $tenantId) {
            return;
        }

        $table = $model->getTable();
        $strict = config('laravel_accounting.strict_multi_tenancy_check', true);

        // Under the default strict check, a row with a null tenant_id is
        // never visible to an authenticated tenant - that would make any row
        // created without an authenticated user (e.g. by a queued job)
        // permanently visible to every tenant once one is matched. Setting
        // strict_multi_tenancy_check to false opts into treating null
        // tenant_id as deliberately global/shared data instead.
        $builder->where(static function ($query) use ($table, $tenantId, $strict) {
            $query->where("$table.tenant_id", $tenantId);

            if (! $strict) {
                $query->orWhereNull("$table.tenant_id");
            }
        });
    }
}
