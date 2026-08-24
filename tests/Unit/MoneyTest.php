<?php

use App\Support\Money;

it('normalizes vendor names', function () {
    expect(Money::normalizeName('  SPBU Pertamina!! '))->toBe('spbu pertamina');
});

it('compares money with tolerance', function () {
    expect(Money::equal(100.00, 100.50, 1))->toBeTrue()
        ->and(Money::equal(100.00, 102.00, 1))->toBeFalse();
});
