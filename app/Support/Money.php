<?php

namespace App\Support;

class Money
{
    public static function of(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }

    public static function add(mixed $a, mixed $b): string
    {
        return bcadd(self::of($a), self::of($b), 2);
    }

    public static function sub(mixed $a, mixed $b): string
    {
        return bcsub(self::of($a), self::of($b), 2);
    }

    public static function sum(iterable $values): string
    {
        $total = '0.00';

        foreach ($values as $value) {
            $total = self::add($total, $value);
        }

        return $total;
    }

    public static function max(mixed $a, mixed $b): string
    {
        return bccomp(self::of($a), self::of($b), 2) >= 0 ? self::of($a) : self::of($b);
    }

    public static function compare(mixed $a, mixed $b): int
    {
        return bccomp(self::of($a), self::of($b), 2);
    }

    public static function isZero(mixed $value): bool
    {
        return self::compare($value, '0') === 0;
    }

    public static function isPositive(mixed $value): bool
    {
        return self::compare($value, '0') > 0;
    }
}
