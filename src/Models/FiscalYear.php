<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\HasUuid;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

/**
 * @mixin Builder<FiscalYear>
 */
class FiscalYear extends Model
{
    use HasTablePrefix, HasUuid, MultiTenant;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'closed_at',
        'closed_by_user_id',
        'tenant_id',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_fiscal_years';
    }

    // Relationships

    public function fiscalPeriods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class, 'fiscal_year_id');
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

    // Helper Methods

    public function canBeClosed(): bool
    {
        return ! $this->is_closed && $this->fiscalPeriods()->where('is_closed', false)->count() === 0;
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

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_closed' => 'boolean',
        ];
    }
}
