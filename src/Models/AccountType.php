<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;

class AccountType extends Model
{
    use HasTablePrefix;

    protected $fillable = [
        'code',
        'name',
        'normal_balance',
        'report_section',
        'description',
        'is_active',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_account_types';
    }

    // Relationships

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'account_type_id');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    // Helper Methods

    public function isDebitType(): bool
    {
        return $this->normal_balance === 'debit';
    }

    public function isCreditType(): bool
    {
        return $this->normal_balance === 'credit';
    }

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:'.config('laravel_accounting.table_prefix', 'la').'_account_types,code',
            'name' => 'required|string|max:100',
            'normal_balance' => 'required|in:debit,credit',
            'report_section' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
