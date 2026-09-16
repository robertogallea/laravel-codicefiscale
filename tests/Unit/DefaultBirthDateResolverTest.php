<?php

use Robertogallea\CodiceFiscale\Data\BirthDateResolutionContext;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Parsing\BirthDate\DefaultBirthDateResolver;
use Tests\Support\InMemoryBirthPlaceRepository;

function contextFor(array $candidates, DateTimeImmutable $referenceDate, InMemoryBirthPlaceRepository $repository = new InMemoryBirthPlaceRepository()): BirthDateResolutionContext
{
    return new BirthDateResolutionContext(
        candidates: $candidates,
        referenceDate: $referenceDate,
        birthPlaceCode: BirthPlaceCode::from('H501'),
        birthPlaceRepository: $repository,
    );
}

test('prefers the younger candidate when both are plausible and birthplace history is inconclusive', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    $context = contextFor(
        [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-01-01')],
        new DateTimeImmutable('2026-08-09'),
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('2026-01-01'));
});

test('excludes a candidate after the reference date', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    $context = contextFor(
        [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-12-31')],
        new DateTimeImmutable('2026-08-09'),
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('1926-01-01'));
});

test('excludes a candidate whose exact age at the reference date exceeds maxAge', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 100);
    $context = contextFor(
        [new DateTimeImmutable('1925-08-09'), new DateTimeImmutable('2026-01-01')],
        new DateTimeImmutable('2026-08-09'),
    );

    // 1925-08-09 is exactly 101 years before the reference date - over maxAge.
    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('2026-01-01'));
});

test('keeps a candidate whose exact age equals maxAge on the reference date', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 100);
    $context = contextFor(
        [new DateTimeImmutable('1926-08-09')],
        new DateTimeImmutable('2026-08-09'),
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('1926-08-09'));
});

test('computes exact age, not calendar-year subtraction, on both sides of the candidate birthday', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 25);

    // Birthday not yet reached on the reference date: true age is still
    // 25, even though the calendar years differ by 26.
    $beforeBirthday = contextFor(
        [new DateTimeImmutable('2000-06-15')],
        new DateTimeImmutable('2026-06-14'),
    );
    expect($resolver->resolve($beforeBirthday))->toEqual(new DateTimeImmutable('2000-06-15'));

    // Exactly on the birthday: true age is now 26, over maxAge.
    $onBirthday = contextFor(
        [new DateTimeImmutable('2000-06-15')],
        new DateTimeImmutable('2026-06-15'),
    );
    expect($resolver->resolve($onBirthday))->toBeNull();
});

test('returns null when no candidate is plausible', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 5);
    $context = contextFor(
        [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-12-31')],
        new DateTimeImmutable('2026-08-09'),
    );

    expect($resolver->resolve($context))->toBeNull();
});

test('returns the sole plausible candidate when only one is given', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    $context = contextFor(
        [new DateTimeImmutable('2026-02-28')],
        new DateTimeImmutable('2026-08-09'),
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('2026-02-28'));
});

test('birthplace history selects the older candidate when it is valid only for that date', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    $repository = new InMemoryBirthPlaceRepository(
        new DomesticBirthPlace(BirthPlaceCode::from('H501'), 'ROMA', 'RM', '058091', new DateTimeImmutable('1900-01-01'), new DateTimeImmutable('1950-01-01')),
    );
    $context = contextFor(
        [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-01-01')],
        new DateTimeImmutable('2026-08-09'),
        $repository,
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('1926-01-01'));
});

/**
 * #117 / #119 - under attributability a code instituted after both
 * candidates is attributable for both, so this is a fall-back case;
 * it used to be an on-date tie-break in favour of the younger date.
 */
test('falls back to the younger candidate when the code was instituted after both dates', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    $repository = new InMemoryBirthPlaceRepository(
        new DomesticBirthPlace(BirthPlaceCode::from('H501'), 'ROMA', 'RM', '058091', new DateTimeImmutable('2020-01-01')),
    );
    $context = contextFor(
        [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-01-01')],
        new DateTimeImmutable('2026-08-09'),
        $repository,
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('2026-01-01'));
});

test('falls back to the younger candidate when birthplace history is valid for both dates', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    $repository = new InMemoryBirthPlaceRepository(
        new DomesticBirthPlace(BirthPlaceCode::from('H501'), 'ROMA', 'RM', '058091', new DateTimeImmutable('1900-01-01')),
    );
    $context = contextFor(
        [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-01-01')],
        new DateTimeImmutable('2026-08-09'),
        $repository,
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('2026-01-01'));
});

test('falls back to the younger candidate when birthplace history is valid for neither date', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    // No records at all for this code: neither candidate resolves to a BirthPlace.
    $context = contextFor(
        [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-01-01')],
        new DateTimeImmutable('2026-08-09'),
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('2026-01-01'));
});

/**
 * #117 / #119 - the tie-breaker uses attributability (ADR-0011,
 * amending ADR-0006): a code whose every era ended before a candidate
 * date rules that candidate out.
 */
test('birthplace history rules out a candidate after the code ceased to exist, even when the code was valid on neither date', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    // H837 (1948-03-14 -> 1974-09-18): not valid in 1926, ceased before
    // 2026. Nobody born in 2026 can carry it; someone born in 1926 can.
    $context = new BirthDateResolutionContext(
        candidates: [new DateTimeImmutable('1926-01-01'), new DateTimeImmutable('2026-01-01')],
        referenceDate: new DateTimeImmutable('2026-08-09'),
        birthPlaceCode: BirthPlaceCode::from('H837'),
        birthPlaceRepository: new InMemoryBirthPlaceRepository(sanFeliceBeforeMerger()),
    );

    expect($resolver->resolve($context))->toEqual(new DateTimeImmutable('1926-01-01'));
});

test('falls back to the context reference date when the resolver has none configured', function () {
    $resolver = new DefaultBirthDateResolver(maxAge: 120);
    $today = new DateTimeImmutable('today');
    $context = contextFor([$today], $today);

    expect($resolver->resolve($context))->toEqual($today);
});
