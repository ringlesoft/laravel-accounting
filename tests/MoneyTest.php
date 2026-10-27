<?php

use RingleSoft\LaravelAccounting\Support\Money;

it('normalizes every input to a 4-decimal string', function () {
    expect(Money::of('12.5'))->toBe('12.5000')
        ->and(Money::of(5))->toBe('5.0000')
        ->and(Money::of(0.1 + 0.2))->toBe('0.3000')
        ->and(Money::of(null))->toBe('0.0000')
        ->and(Money::of(' 7 '))->toBe('7.0000')
        ->and(Money::of('1.5E+3'))->toBe('1500.0000')
        ->and(Money::of('2.5e-3'))->toBe('0.0025');
});

it('rounds half away from zero at 4 decimal places', function () {
    expect(Money::of('1.00005'))->toBe('1.0001')
        ->and(Money::of('1.00004999'))->toBe('1.0000')
        ->and(Money::of('-1.00005'))->toBe('-1.0001')
        ->and(Money::of('-0.00001'))->toBe('0.0000');
});

it('keeps all 18 integer digits of a DECIMAL(22, 4) value', function () {
    $max = '999999999999999999.9999';

    expect(Money::of($max))->toBe($max)
        ->and(Money::sub($max, '0.0001'))->toBe('999999999999999999.9998')
        ->and(Money::add('123456789012345678.1234', '0.0001'))->toBe('123456789012345678.1235');
});

it('does arithmetic without float drift', function () {
    expect(Money::sum(['0.1000', '0.2000', '0.3000']))->toBe('0.6000')
        ->and(Money::sub('100', '33.3333'))->toBe('66.6667')
        ->and(Money::mul('100.0001', 0.18))->toBe('18.0000')
        ->and(Money::div('100', 3))->toBe('33.3333')
        ->and(Money::abs('-4.5'))->toBe('4.5000')
        ->and(Money::negate('4.5'))->toBe('-4.5000')
        ->and(Money::max('1', '-2', '3.5'))->toBe('3.5000');
});

it('compares at 4 decimal places', function () {
    expect(Money::equals('1.0000', 1))->toBeTrue()
        ->and(Money::equals('1.0001', '1'))->toBeFalse()
        ->and(Money::isZero('0.00004'))->toBeTrue()
        ->and(Money::isZero('0.0001'))->toBeFalse()
        ->and(Money::isPositive('0.0050'))->toBeTrue()
        ->and(Money::isNegative('-0.0001'))->toBeTrue()
        ->and(Money::compare('2', '10'))->toBe(-1);
});

it('formats without going through a float', function () {
    expect(Money::format('1234567.5'))->toBe('1,234,567.5000')
        ->and(Money::format('-1234567.125', 2))->toBe('-1,234,567.13')
        ->and(Money::format('999999999999999999.9999'))->toBe('999,999,999,999,999,999.9999');
});

it('rejects invalid amounts', function (mixed $value) {
    Money::of($value);
})->throws(InvalidArgumentException::class)->with(['abc', '1,000', '12.3.4', INF, NAN]);

it('rejects division by zero', function () {
    Money::div('10', 0);
})->throws(InvalidArgumentException::class);
