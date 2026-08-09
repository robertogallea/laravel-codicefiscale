<?php

test('exposes its code, name, province and ISTAT code', function () {
    $place = abbadiaCerretoUnderLodi();

    expect($place->code()->value())->toBe('A004')
        ->and($place->name())->toBe('ABBADIA CERRETO')
        ->and($place->province())->toBe('LO')
        ->and($place->istatCode())->toBe('098001');
});

test('wasValidOn() is true for a date within the era, false outside it', function () {
    $milano = abbadiaCerretoUnderMilano();

    expect($milano->wasValidOn(new DateTimeImmutable('1900-01-01')))->toBeTrue()
        ->and($milano->wasValidOn(new DateTimeImmutable('1992-04-15')))->toBeTrue()
        ->and($milano->wasValidOn(new DateTimeImmutable('1992-04-16')))->toBeFalse()
        ->and($milano->wasValidOn(new DateTimeImmutable('1860-01-01')))->toBeFalse();
});

test('wasValidOn() is true indefinitely into the future for an open-ended (still valid) record', function () {
    $lodi = abbadiaCerretoUnderLodi();

    expect($lodi->wasValidOn(new DateTimeImmutable('1992-04-16')))->toBeTrue()
        ->and($lodi->wasValidOn(new DateTimeImmutable('today')))->toBeTrue()
        ->and($lodi->wasValidOn(new DateTimeImmutable('2100-01-01')))->toBeTrue()
        ->and($lodi->wasValidOn(new DateTimeImmutable('1992-04-15')))->toBeFalse();
});
