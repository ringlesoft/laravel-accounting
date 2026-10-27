<?php

namespace RingleSoft\LaravelAccounting\Support;

use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Enums\CurrencyCode;

/**
 * Fixed-scale decimal arithmetic for monetary amounts.
 *
 * Every amount column is DECIMAL(22, 4), so every amount the package hands
 * out is a numeric string with exactly SCALE decimal places (e.g. "12.3400").
 * Floats are never used for money: a double carries ~15-16 significant
 * digits, while a DECIMAL(22, 4) column holds 22.
 *
 * Inputs accept string|int|float for convenience; floats are converted via
 * their shortest string representation before any arithmetic happens.
 * Results are rounded half away from zero to SCALE places.
 *
 * SCALE is the storage ceiling. Amounts are posted and displayed at the
 * configured `decimals` (decimals()), by default each currency's own
 * decimal places: 2 for USD, 0 for JPY, 3 for KWD.
 */
final class Money
{
    /**
     * Decimal places stored in every amount column.
     */
    public const SCALE = 4;

    public const ZERO = '0.0000';

    /**
     * Decimal places used when neither the config nor the currency gives any.
     */
    public const DEFAULT_DECIMALS = 2;

    /**
     * Precision used for intermediate multiplication/division results,
     * before rounding back to SCALE.
     */
    private const WORK_SCALE = 16;

    /**
     * Normalize a value to a SCALE-place numeric string.
     */
    public static function of(string|int|float|null $value): string
    {
        return self::roundNumber(self::number($value));
    }

    /**
     * Round to $decimals places (at most SCALE), returned as a SCALE-place
     * string: round('12.3456', 2) is '12.3500'.
     */
    public static function round(string|int|float|null $value, int $decimals): string
    {
        return self::of(self::roundNumber(self::number($value), min(max(0, $decimals), self::SCALE)));
    }

    /**
     * Round to the decimals amounts are posted at for $currency (see decimals()).
     */
    public static function forCurrency(string|int|float|null $value, CurrencyCode|string|null $currency = null): string
    {
        return self::round($value, self::decimals($currency));
    }

    /**
     * @param  iterable<string|int|float|null>  $values
     */
    public static function sum(iterable $values): string
    {
        $total = '0';
        foreach ($values as $value) {
            $total = bcadd($total, self::number($value), self::WORK_SCALE);
        }

        return self::roundNumber($total);
    }

    public static function add(string|int|float|null ...$values): string
    {
        return self::sum($values);
    }

    public static function sub(string|int|float|null $minuend, string|int|float|null $subtrahend): string
    {
        return self::roundNumber(bcsub(self::number($minuend), self::number($subtrahend), self::WORK_SCALE));
    }

    /**
     * Multiply an amount by a factor (a rate, a quantity, ...). The factor
     * is used at full precision; only the result is rounded.
     */
    public static function mul(string|int|float|null $amount, string|int|float $factor): string
    {
        return self::roundNumber(bcmul(self::number($amount), self::number($factor), self::WORK_SCALE));
    }

    /**
     * Divide an amount by a divisor (e.g. 1 + tax rate). Only the result is rounded.
     */
    public static function div(string|int|float|null $amount, string|int|float $divisor): string
    {
        $divisor = self::number($divisor);
        if (bccomp($divisor, '0', self::WORK_SCALE) === 0) {
            throw new InvalidArgumentException('Division by zero');
        }

        return self::roundNumber(bcdiv(self::number($amount), $divisor, self::WORK_SCALE));
    }

    public static function abs(string|int|float|null $value): string
    {
        return ltrim(self::of($value), '-');
    }

    public static function negate(string|int|float|null $value): string
    {
        return self::sub(0, $value);
    }

    public static function max(string|int|float|null ...$values): string
    {
        $max = null;
        foreach ($values as $value) {
            $value = self::of($value);
            if ($max === null || self::compare($value, $max) > 0) {
                $max = $value;
            }
        }

        return $max ?? self::ZERO;
    }

    /**
     * Compare two amounts at SCALE: -1, 0 or 1.
     */
    public static function compare(string|int|float|null $left, string|int|float|null $right): int
    {
        return bccomp(self::of($left), self::of($right), self::SCALE);
    }

    public static function equals(string|int|float|null $left, string|int|float|null $right): bool
    {
        return self::compare($left, $right) === 0;
    }

    public static function isZero(string|int|float|null $value): bool
    {
        return self::compare($value, 0) === 0;
    }

    public static function isPositive(string|int|float|null $value): bool
    {
        return self::compare($value, 0) > 0;
    }

    public static function isNegative(string|int|float|null $value): bool
    {
        return self::compare($value, 0) < 0;
    }

    /**
     * Format for display without going through a float: thousands separated
     * and rounded half away from zero, to $decimals places when given, or to
     * decimals($currency) - the same places amounts are posted at. Display
     * only - never feed the result back into calculations.
     */
    public static function format(
        string|int|float|null $value,
        ?int $decimals = null,
        CurrencyCode|string|null $currency = null,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ',',
    ): string {
        $decimals = max(0, $decimals ?? self::decimals($currency));

        $rounded = self::roundNumber(self::number($value), $decimals);
        $negative = str_starts_with($rounded, '-');
        [$integer, $fraction] = array_pad(explode('.', ltrim($rounded, '-')), 2, '');

        $integer = strrev(implode($thousandsSeparator, str_split(strrev($integer), 3)));

        return ($negative ? '-' : '').$integer.($decimals > 0 ? $decimalSeparator.$fraction : '');
    }

    /**
     * The decimal places amounts are posted and displayed at: the `decimals`
     * config value when it is an int, otherwise the currency's own decimal
     * places ($currency, or `default_currency_code`). Never more than SCALE.
     */
    public static function decimals(CurrencyCode|string|null $currency = null): int
    {
        $config = self::config();
        $decimals = $config['decimals'] ?? null;

        if (is_numeric($decimals)) {
            return min(max(0, (int) $decimals), self::SCALE);
        }

        $currency ??= $config['default_currency_code'] ?? null;
        if (is_string($currency)) {
            $currency = CurrencyCode::tryFrom(strtoupper($currency));
        }

        return min($currency?->decimalDigits() ?? self::DEFAULT_DECIMALS, self::SCALE);
    }

    /**
     * The package config, or [] outside a booted Laravel application.
     */
    private static function config(): array
    {
        try {
            return (array) config('laravel_accounting', []);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Round a plain decimal string half away from zero. bcmath truncates
     * towards zero, so half a unit in the last place is added first.
     */
    private static function roundNumber(string $number, int $scale = self::SCALE): string
    {
        $half = $scale > 0 ? '0.'.str_repeat('0', $scale).'5' : '0.5';

        $rounded = str_starts_with($number, '-')
            ? bcsub($number, $half, $scale)
            : bcadd($number, $half, $scale);

        // Avoid "-0.0000"
        if (bccomp($rounded, '0', $scale) === 0) {
            return $scale > 0 ? '0.'.str_repeat('0', $scale) : '0';
        }

        return $rounded;
    }

    /**
     * Convert an input to a plain decimal string bcmath accepts (no exponent, no whitespace).
     */
    private static function number(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new InvalidArgumentException('Amount must be a finite number');
            }

            $value = (string) $value;
        }

        $value = trim($value);

        if (preg_match('/^[+-]?(\d+(\.\d*)?|\.\d+)$/', $value)) {
            return ltrim($value, '+');
        }

        if (preg_match('/^[+-]?(\d+(\.\d*)?|\.\d+)[eE][+-]?\d+$/', $value)) {
            return self::expandExponent($value);
        }

        throw new InvalidArgumentException("Invalid amount: '{$value}'");
    }

    /**
     * Expand scientific notation ("1.5E+20", "2.5e-5") exactly, by shifting the decimal point.
     */
    private static function expandExponent(string $value): string
    {
        [$mantissa, $exponent] = preg_split('/[eE]/', $value);
        $exponent = (int) $exponent;

        $sign = str_starts_with($mantissa, '-') ? '-' : '';
        [$integer, $fraction] = array_pad(explode('.', ltrim($mantissa, '+-')), 2, '');
        $digits = $integer.$fraction;
        $point = strlen($integer) + $exponent;

        if ($point <= 0) {
            $result = '0.'.str_repeat('0', -$point).$digits;
        } elseif ($point >= strlen($digits)) {
            $result = $digits.str_repeat('0', $point - strlen($digits));
        } else {
            $result = substr($digits, 0, $point).'.'.substr($digits, $point);
        }

        $result = ltrim($result, '0');
        if ($result === '' || str_starts_with($result, '.')) {
            $result = '0'.$result;
        }

        return $sign.$result;
    }
}
