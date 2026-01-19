<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

class JournalEntry extends Model
{
    use HasTablePrefix, MultiTenant;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'posted_at',
        'description',
        'reference',
        'postable_id',
        'postable_type',
        'currency_code',
        'exchange_rate',
        'fiscal_period_id',
        'created_by_user_id',
        'posted_by_user_id',
        'voided_by_user_id',
        'reversal_of_id',
        'is_posted',
        'is_voided',
        'is_reversed',
        'voided_at',
        'tenant_id',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
        'is_posted' => 'boolean',
        'is_voided' => 'boolean',
        'is_reversed' => 'boolean',
        'exchange_rate' => 'decimal:10',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_journal_entries';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journalEntry) {
            if (empty($journalEntry->transaction_number)) {
                $journalEntry->transaction_number = static::generateTransactionNumber();
            }
            if (empty($journalEntry->currency_code)) {
                $journalEntry->currency_code = config('laravel_accounting.default_currency_code', 'USD');
            }
            if (empty($journalEntry->exchange_rate)) {
                $journalEntry->exchange_rate = 1.0;
            }
        });
    }

    // Relationships

    public function items(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class, 'journal_entry_id');
    }

    public function postable(): MorphTo
    {
        return $this->morphTo();
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_user_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'posted_by_user_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'voided_by_user_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    // Scopes

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('is_posted', true);
    }

    public function scopeUnposted(Builder $query): Builder
    {
        return $query->where('is_posted', false);
    }

    public function scopeByPeriod(Builder $query, $periodId): Builder
    {
        return $query->where('fiscal_period_id', $periodId);
    }

    public function scopeByTenant(Builder $query, $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeNotVoided(Builder $query): Builder
    {
        return $query->where('is_voided', false);
    }

    public function scopeNotReversed(Builder $query): Builder
    {
        return $query->where('is_reversed', false);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Helper Methods

    public function isBalanced(): bool
    {
        $totalDebits = $this->items()->sum('debit_amount');
        $totalCredits = $this->items()->sum('credit_amount');

        return bccomp((string) $totalDebits, (string) $totalCredits, 2) === 0;
    }

    public function getTotalDebits(): float
    {
        return (float) $this->items()->sum('debit_amount');
    }

    public function getTotalCredits(): float
    {
        return (float) $this->items()->sum('credit_amount');
    }

    public function canBePosted(): bool
    {
        return !$this->is_posted
            && !$this->is_voided
            && $this->isBalanced()
            && $this->items()->count() > 0;
    }

    public function canBeVoided(): bool
    {
        return $this->is_posted && !$this->is_voided && !$this->is_reversed;
    }

    public function canBeReversed(): bool
    {
        return $this->is_posted && !$this->is_voided && !$this->is_reversed;
    }

    // Transaction Number Generation

    protected static function generateTransactionNumber(): string
    {
        $prefix = config('laravel_accounting.transaction_number_prefix', 'JE');
        $date = now()->format('Ymd');

        $lastEntry = static::whereDate('created_at', now())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastEntry ? (int) substr($lastEntry->transaction_number, -6) + 1 : 1;

        return sprintf('%s-%s-%06d', $prefix, $date, $sequence);
    }

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'transaction_number' => 'nullable|string|max:50|unique:'.config('laravel_accounting.table_prefix', 'la').'_journal_entries,transaction_number',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:100',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0',
            'fiscal_period_id' => 'required|exists:'.config('laravel_accounting.table_prefix', 'la').'_fiscal_periods,id',
        ];
    }
}
