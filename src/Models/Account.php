<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

class Account extends Model
{
    use HasTablePrefix, MultiTenant;

    protected $fillable = [
        'code',
        'name',
        'account_type_id',
        'category',
        'sub_category',
        'report_section',
        'parent_id',
        'currency_code',
        'description',
        'is_active',
        'is_system_account',
        'allows_manual_entries',
        'preset',
        'tenant_id',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system_account' => 'boolean',
        'allows_manual_entries' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_accounts';
    }

    // Relationships

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class, 'account_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'account_id');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, $typeId): Builder
    {
        return $query->where('account_type_id', $typeId);
    }

    public function scopeByTenant(Builder $query, $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeSystemAccounts(Builder $query): Builder
    {
        return $query->where('is_system_account', true);
    }

    public function scopeManualEntryAllowed(Builder $query): Builder
    {
        return $query->where('allows_manual_entries', true);
    }

    // Hierarchical Methods

    public function getFullCode(): string
    {
        $codes = [$this->code];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($codes, $parent->code);
            $parent = $parent->parent;
        }

        return implode('.', $codes);
    }

    public function getAncestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    public function getDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    public function isParentOf(Account $account): bool
    {
        return $this->id === $account->parent_id;
    }

    public function isChildOf(Account $account): bool
    {
        return $this->parent_id === $account->id;
    }

    public function isDescendantOf(Account $account): bool
    {
        $parent = $this->parent;

        while ($parent) {
            if ($parent->id === $account->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'account_type_id' => 'required|exists:'.config('laravel_accounting.table_prefix', 'la').'_account_types,id',
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'report_section' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:'.config('laravel_accounting.table_prefix', 'la').'_accounts,id',
            'currency_code' => 'required|string|size:3',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_system_account' => 'boolean',
            'allows_manual_entries' => 'boolean',
        ];
    }
}
