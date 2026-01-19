<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;

class ExchangeRate extends Model
{
    use HasTablePrefix;

    protected $fillable = [
        'from_currency_code',
        'to_currency_code',
        'rate',
        'effective_date',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'rate' => 'decimal:10',
        'effective_date' => 'date',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laravel_accounting.table_prefix', 'la').'_exchange_rates';
    }

    // Scopes

    public function scopeForCurrencyPair(Builder $query, string $from, string $to): Builder
    {
        return $query->where('from_currency_code', $from)
            ->where('to_currency_code', $to);
    }

    public function scopeEffectiveOn(Builder $query, $date): Builder
    {
        return $query->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc');
    }

    // Static Helper Methods

    public static function getRate(string $from, string $to, $date = null): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ? Carbon::parse($date) : now();

        $rate = static::forCurrencyPair($from, $to)
            ->effectiveOn($date)
            ->first();

        if ($rate) {
            return (float) $rate->rate;
        }

        // Try inverse rate
        $inverseRate = static::forCurrencyPair($to, $from)
            ->effectiveOn($date)
            ->first();

        if ($inverseRate && $inverseRate->rate > 0) {
            return 1 / (float) $inverseRate->rate;
        }

        return null;
    }

    public static function convert(float $amount, string $from, string $to, $date = null): ?float
    {
        $rate = static::getRate($from, $to, $date);

        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    // Helper Methods

    public function getInverseRate(): float
    {
        return $this->rate > 0 ? 1 / (float) $this->rate : 0;
    }

    // Validation Rules

    public static function validationRules(): array
    {
        return [
            'from_currency_code' => 'required|string|size:3',
            'to_currency_code' => 'required|string|size:3|different:from_currency_code',
            'rate' => 'required|numeric|min:0.0000000001',
            'effective_date' => 'required|date',
        ];
    }

    public static function customValidationMessages(): array
    {
        return [
            'to_currency_code.different' => 'The from and to currencies must be different.',
            'rate.min' => 'The exchange rate must be greater than zero.',
        ];
    }
}
