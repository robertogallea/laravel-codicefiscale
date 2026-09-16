<?php

/**
 * Attributability rule for #117 / #118 (ADR-0011): a BirthPlaceCode
 * is judged against a birth date the way the tax authority assigns
 * it - as of issue time, not birth time.
 */

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\CountryCode;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Support\BirthPlaceAttributability;

test('a code instituted after the birth date is attributable, its era being the one instituted after', function () {
    // The reporter's case from #117: born 1951 in San Felice, code
    // issued after the 1974 merger as I603 - AdE-valid.
    $era = BirthPlaceAttributability::era([senaleSanFelice()], new DateTimeImmutable('1951-01-01'));

    expect($era)->not->toBeNull()
        ->and($era->name())->toBe('SENALE-SAN FELICE');
});

test('a code whose every era ended before the birth date is not attributable', function () {
    // H837 ceased on 1974-09-18: nobody born in 1990 could carry it.
    expect(BirthPlaceAttributability::era([sanFeliceBeforeMerger()], new DateTimeImmutable('1990-01-01')))->toBeNull();
});

test('the era valid on the birth date wins over later eras of the same code, whatever the list order', function () {
    $era = BirthPlaceAttributability::era(
        [abbadiaCerretoUnderLodi(), abbadiaCerretoUnderMilano()],
        new DateTimeImmutable('1950-01-01'),
    );

    expect($era->province())->toBe('MI');
});

test('among eras all instituted after the birth date, the earliest is the attributable one, whatever the list order', function () {
    // Both eras post-date the birth; the earliest is the closest to
    // the moment the code could first have been issued.
    $era = BirthPlaceAttributability::era(
        [abbadiaCerretoUnderLodi(), abbadiaCerretoUnderMilano()],
        new DateTimeImmutable('1850-01-01'),
    );

    expect($era->province())->toBe('MI');
});

test('a code valid on the birth date is attributable with that very era', function () {
    $era = BirthPlaceAttributability::era([sanFeliceBeforeMerger()], new DateTimeImmutable('1951-01-01'));

    expect($era->name())->toBe('SAN FELICE');
});

test('a foreign code with a maximally wide window is attributable for any date', function () {
    $usa = new ForeignBirthPlace(
        code: BirthPlaceCode::from('Z404'),
        name: "STATI UNITI D'AMERICA",
        country: CountryCode::from('USA'),
        validFrom: new DateTimeImmutable('1900-01-01'),
    );

    expect(BirthPlaceAttributability::era([$usa], new DateTimeImmutable('1850-01-01')))->toBe($usa)
        ->and(BirthPlaceAttributability::era([$usa], new DateTimeImmutable('2020-01-01')))->toBe($usa);
});

test('an empty history is never attributable', function () {
    expect(BirthPlaceAttributability::era([], new DateTimeImmutable('1951-01-01')))->toBeNull();
});
