<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

class FiscalPeriod extends Model
{
    use HasTablePrefix, MultiTenant;

    protected $fillable = [
        'fiscal_year_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'is_locked',
        'closed_at',
        'closed_by_user_id',
        'tenant_id',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'is_locked' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_fiscal_periods';
    }

    // Relationships

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'fiscal_period_id');
    }

    public function accountBalances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'fiscal_period_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'closed_by_user_id');
    }

    // Scopes

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('is_closed', true);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    public function scopeByTenant(Builder $query, $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByYear(Builder $query, $yearId): Builder
    {
        return $query->where('fiscal_year_id', $yearId);
    }

    // Helper Methods

    public function canBeClosed(): bool
    {
        return !$this->is_closed && !$this->is_locked;
    }

    public function canBeLocked(): bool
    {
        return $this->is_closed && !$this->is_locked;
    }

    public function canBeReopened(): bool
    {
        return $this->is_closed && !$this->is_locked;
    }

    public function isActive(): bool
    {
        $today = now();
        return $today->between($this->start_date, $this->end_date);
    }

    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function hasPostedEntries(): bool
    {
        return $this->journalEntries()->where('is_posted', true)->exists();
    }

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'fiscal_year_id' => 'required|exists:'.config('laravel_accounting.table_prefix', 'la').'_fiscal_years,id',
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_closed' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public static function customValidationMessages(): array
    {
        return [
            'end_date.after' => 'The end date must be after the start date.',
        ];
    }
}
