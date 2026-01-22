<?php

namespace RingleSoft\LaravelAccounting\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;

class AccountValidator
{
    /**
     * Get validation rules for account
     */
    public static function rules(?int $ignoreId = null): array
    {
        $tablePrefix = config('laravel_accounting.table_prefix', 'la');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique("{$tablePrefix}_accounts", 'code')->ignore($ignoreId),
            ],
            'name' => 'required|string|max:255',
            'account_type_id' => [
                'required',
                'exists:' . $tablePrefix . '_account_types,id',
            ],
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'report_section' => 'nullable|string|max:100',
            'parent_id' => [
                'nullable',
                'exists:' . $tablePrefix . '_accounts,id',
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
    public static function validate(array $data, ?int $ignoreId = null): void
    {
        $validator = Validator::make($data, self::rules($ignoreId));

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }
    }

    /**
     * Get a validator instance
     */
    public static function make(array $data, ?int $ignoreId = null): \Illuminate\Validation\Validator
    {
        return Validator::make($data, self::rules($ignoreId));
    }
}
