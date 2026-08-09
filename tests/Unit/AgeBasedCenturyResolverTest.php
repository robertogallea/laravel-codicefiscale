<?php

use Robertogallea\CodiceFiscale\Parsing\Century\AgeBasedCenturyResolver;

test('prefers the more recent candidate when both are within maxAge and not in the future', function () {
    $resolver = new AgeBasedCenturyResolver(maxAge: 120, referenceDate: new DateTimeImmutable('2026-08-09'));

    // 1926 (age 100) and 2026 (age 0) are both plausible; prefer the younger reading.
    expect($resolver->resolve([1926, 2026]))->toBe(2026);
});

test('picks the only plausible candidate when the other is in the future', function () {
    $resolver = new AgeBasedCenturyResolver(maxAge: 120, referenceDate: new DateTimeImmutable('2005-01-01'));

    // 1926 (age 79) is plausible; 2026 is in the future and excluded outright.
    expect($resolver->resolve([1926, 2026]))->toBe(1926);
});

test('picks the only plausible candidate when the other would exceed maxAge', function () {
    $resolver = new AgeBasedCenturyResolver(maxAge: 50, referenceDate: new DateTimeImmutable('2026-08-09'));

    // 1926 (age 100) exceeds maxAge 50; 2026 (age 0) is the only plausible reading.
    expect($resolver->resolve([1926, 2026]))->toBe(2026);
});

test('falls back to the more recent candidate when neither is plausible', function () {
    $resolver = new AgeBasedCenturyResolver(maxAge: 5, referenceDate: new DateTimeImmutable('1975-01-01'));

    // 1926 (age 49) exceeds maxAge 5; 2026 is in the future. Neither is
    // plausible, so the more recent candidate is the least-bad fallback.
    expect($resolver->resolve([1926, 2026]))->toBe(2026);
});

test('defaults to today when no reference date is given', function () {
    $resolver = new AgeBasedCenturyResolver(maxAge: 120);

    $currentYear = (int) (new DateTimeImmutable('today'))->format('Y');
    $twoDigitYear = (int) substr((string) $currentYear, -2);

    expect($resolver->resolve([1900 + $twoDigitYear, 2000 + $twoDigitYear]))->toBe(2000 + $twoDigitYear);
});
