<?php

namespace RingleSoft\LaravelAccounting\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;

class JournalEntryValidator
{
    /**
     * Get validation rules for journal entry
     */
    public static function rules(?int $ignoreId = null): array
    {
        $tablePrefix = config('laravel_accounting.table_prefix', 'la');

        return [
            'transaction_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique("{$tablePrefix}_journal_entries", 'transaction_number')->ignore($ignoreId),
            ],
            'transaction_date' => [
                'required',
                'date',
                'after_or_equal:'.now()->subYears(10)->toDateString(),
                'before_or_equal:'.now()->addDay()->toDateString(),
            ],
            'description' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in(array_column(CurrencyCode::cases(), 'value')),
            ],
            'exchange_rate' => 'required|numeric|min:0.0001|max:9999999999999.9999',
            'fiscal_period_id' => [
                'required',
                'exists:'.$tablePrefix.'_fiscal_periods,id',
            ],
            'postable_id' => config('laravel_accounting.use_uuid', false)
                ? 'nullable|string|max:36'
                : 'nullable|integer',
            'postable_type' => 'nullable|string|required_with:postable_id',
            'posted_by_user_id' => 'nullable|exists:users,id',
            'created_by_user_id' => 'nullable|exists:users,id',
            'voided_by_user_id' => 'nullable|exists:users,id',
            'reversal_of_id' => [
                'nullable',
                'exists:'.$tablePrefix.'_journal_entries,id',
            ],
            'is_posted' => 'boolean',
            'is_voided' => 'boolean',
            'is_reversed' => 'boolean',
            'posted_at' => 'nullable|date|required_if:is_posted,true',
            'voided_at' => 'nullable|date|required_if:is_voided,true',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Validate journal entry data
     *
     * @throws InvalidVoucherException
     */
    public static function validate(array $data, ?int $ignoreId = null): void
    {

        if (! empty($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'], true);
        }

        $validator = Validator::make($data, self::rules($ignoreId));

        if ($validator->fails()) {
            throw new InvalidVoucherException($validator->errors()->first());
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
