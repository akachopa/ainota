<?php

namespace App\Support;

final class Money
{
    public static function equal(string|float|int|null $left, string|float|int|null $right, float $tolerance = 0.0): bool
    {
        return abs(self::toFloat($left) - self::toFloat($right)) <= $tolerance;
    }

    public static function toFloat(string|float|int|null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    public static function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }
}
