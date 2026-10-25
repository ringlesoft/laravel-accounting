<?php

namespace RingleSoft\LaravelAccounting\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;
use RingleSoft\LaravelAccounting\Support\ChartStructure;

class AccountValidator
{
    /**
     * Get validation rules for account
     *
     * Code uniqueness is checked within a single tenant, matching the
     * unique(['code', 'tenant_id']) index: $tenantId is the tenant of the
     * account being validated, falling back to the resolved current tenant.
     *
     * The code may be omitted under a parent (one is generated). While the
     * chart structure is enforced, a parent is required: root accounts come
     * from the package's chart only.
     */
    public static function rules(int|string|null $ignoreId = null, int|string|null $tenantId = null): array
    {
        $tablePrefix = config('laravel_accounting.table_prefix', 'la');
        $tenantId ??= app(TenantResolver::class)->resolve();

        return [
            'code' => [
                'required_without:parent_id',
                'nullable',
                'string',
                'max:50',
                Rule::unique("{$tablePrefix}_accounts", 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($ignoreId),
            ],
            'name' => 'required|string|max:255',
            'account_type_id' => [
                'required',
                'exists:'.$tablePrefix.'_account_types,id',
            ],
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'report_section' => 'nullable|string|max:100',
            'parent_id' => [
                ChartStructure::enforced() ? 'required' : 'nullable',
                'exists:'.$tablePrefix.'_accounts,id',
                function ($attribute, $value, $fail) use ($ignoreId) {
                    if ($value && $ignoreId && $value == $ignoreId) {
                        $fail('An account cannot be its own parent.');
                    }
                },
            ],
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in(array_column(CurrencyCode::cases(), 'value')),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'is_system_account' => 'boolean',
            'allows_manual_entries' => 'boolean',
            'preset' => 'nullable|string|max:100',
        ];
    }

    /**
     * Validate account data
     *
     * @throws InvalidArgumentException
     */
    public static function validate(array $data, int|string|null $ignoreId = null): void
    {
        $validator = self::make($data, $ignoreId);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }
    }

    /**
     * Get a validator instance
     *
     * The resolved tenant takes priority over a tenant_id in $data, as
     * MultiTenant overrides a caller-supplied tenant_id with it on create.
     */
    public static function make(array $data, int|string|null $ignoreId = null): \Illuminate\Validation\Validator
    {
        $tenantId = app(TenantResolver::class)->resolve() ?? ($data['tenant_id'] ?? null);

        return Validator::make($data, self::rules($ignoreId, $tenantId));
    }
}
