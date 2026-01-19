<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

class JournalEntryItem extends Model
{
    use HasTablePrefix, MultiTenant;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'description',
        'debit_amount',
        'credit_amount',
        'currency_code',
        'exchange_rate',
        'dimension_id',
        'dimension_type',
        'tenant_id',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:10',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_journal_entry_items';
    }

    // Relationships

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function dimension(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('credit_amount', '>', 0);
    }

    public function scopeByAccount(Builder $query, $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByTenant(Builder $query, $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helper Methods

    public function getAmount(): float
    {
        return $this->debit_amount > 0 ? (float) $this->debit_amount : (float) $this->credit_amount;
    }

    public function getType(): string
    {
        return $this->debit_amount > 0 ? 'debit' : 'credit';
    }

    public function isDebit(): bool
    {
        return $this->debit_amount > 0;
    }

    public function isCredit(): bool
    {
        return $this->credit_amount > 0;
    }

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'journal_entry_id' => 'required|exists:'.config('laravel_accounting.table_prefix', 'la').'_journal_entries,id',
            'account_id' => 'required|exists:'.config('laravel_accounting.table_prefix', 'la').'_accounts,id',
            'description' => 'nullable|string',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0',
        ];
    }

    public static function customValidationMessages(): array
    {
        return [
            'debit_amount.min' => 'Debit amount must be zero or positive.',
            'credit_amount.min' => 'Credit amount must be zero or positive.',
        ];
    }
}
