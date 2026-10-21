<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\HasUuid;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

/**
 * @mixin Builder<AccountBalance>
 */
class AccountBalance extends Model
{
    use HasTablePrefix, HasUuid, MultiTenant;

    protected $fillable = [
        'account_id',
        'fiscal_period_id',
        'opening_balance',
        'debit_total',
        'credit_total',
        'closing_balance',
        'currency_code',
        'tenant_id',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_account_balances';
    }

    // Relationships

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    // Scopes

    public function scopeByAccount(Builder $query, $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByPeriod(Builder $query, $periodId): Builder
    {
        return $query->where('fiscal_period_id', $periodId);
    }

    public function scopeByTenant(Builder $query, $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helper Methods

    public function calculateClosingBalance(): float
    {
        $account = $this->account;
        $accountType = $account->accountType;

        if ($accountType->normal_balance === 'debit') {
            return (float) ($this->opening_balance + $this->debit_total - $this->credit_total);
        } else {
            return (float) ($this->opening_balance + $this->credit_total - $this->debit_total);
        }
    }

    public function getNetMovement(): float
    {
        return (float) ($this->debit_total - $this->credit_total);
    }

    public function hasActivity(): bool
    {
        return $this->debit_total > 0 || $this->credit_total > 0;
    }

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'account_id' => 'required|exists:'.config('laravel_accounting.table_prefix', 'la').'_accounts,id',
            'fiscal_period_id' => 'required|exists:'.config('laravel_accounting.table_prefix', 'la').'_fiscal_periods,id',
            'opening_balance' => 'required|numeric',
            'debit_total' => 'required|numeric|min:0',
            'credit_total' => 'required|numeric|min:0',
            'closing_balance' => 'required|numeric',
            'currency_code' => 'required|string|size:3',
        ];
    }
}
