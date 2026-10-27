<?php

namespace RingleSoft\LaravelAccounting\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelAccounting\Support\Money;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\HasUuid;

class ExchangeRate extends Model
{
    use HasTablePrefix, HasUuid;

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

    /**
     * Rates are returned as numeric strings at full precision (never
     * floats), so a converted amount is only rounded once, by Money.
     */
    public static function getRate(string $from, string $to, $date = null): ?string
    {
        if ($from === $to) {
            return '1';
        }

        $date = $date ? Carbon::parse($date) : now();

        $rate = static::forCurrencyPair($from, $to)
            ->effectiveOn($date)
            ->first();

        if ($rate) {
            return (string) $rate->rate;
        }

        // Try inverse rate
        $inverseRate = static::forCurrencyPair($to, $from)
            ->effectiveOn($date)
            ->first();

        if ($inverseRate && bccomp((string) $inverseRate->rate, '0', 10) > 0) {
            return $inverseRate->getInverseRate();
        }

        return null;
    }

    public static function convert(string|int|float $amount, string $from, string $to, $date = null): ?string
    {
        $rate = static::getRate($from, $to, $date);

        if ($rate === null) {
            return null;
        }

        return Money::mul($amount, $rate);
    }

    // Helper Methods

    public function getInverseRate(): string
    {
        $rate = (string) $this->rate;

        return bccomp($rate, '0', 10) > 0 ? bcdiv('1', $rate, 16) : '0';
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
