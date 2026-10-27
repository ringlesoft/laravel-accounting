<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RingleSoft\LaravelAccounting\Support\Money;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\HasUuid;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

/**
 * @mixin Builder<AccountBalance>
 *
 * @property string $opening_balance
 * @property string $debit_total
 * @property string $credit_total
 * @property string $closing_balance
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
        'opening_balance' => 'decimal:4',
        'debit_total' => 'decimal:4',
        'credit_total' => 'decimal:4',
        'closing_balance' => 'decimal:4',
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

    public function calculateClosingBalance(): string
    {
        $account = $this->account;
        $accountType = $account->accountType;

        if ($accountType->normal_balance === 'debit') {
            return Money::sub(Money::add($this->opening_balance, $this->debit_total), $this->credit_total);
        }

        return Money::sub(Money::add($this->opening_balance, $this->credit_total), $this->debit_total);
    }

    public function getNetMovement(): string
    {
        return Money::sub($this->debit_total, $this->credit_total);
    }

    public function hasActivity(): bool
    {
        return Money::isPositive($this->debit_total) || Money::isPositive($this->credit_total);
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
