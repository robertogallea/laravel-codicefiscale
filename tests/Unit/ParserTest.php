<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthDateResolver;
use Robertogallea\CodiceFiscale\Data\BirthDateResolutionContext;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\Generator;
use Robertogallea\CodiceFiscale\Parsing\BirthDate\DefaultBirthDateResolver;
use Robertogallea\CodiceFiscale\Parsing\Parser;
use Tests\Support\InMemoryBirthPlaceRepository;

test('decodes surname code, name code, gender, month, day and birthplace code', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    expect($parsed->surnameCode())->toBe('RSS')
        ->and($parsed->nameCode())->toBe('MRA')
        ->and($parsed->gender())->toBe(Gender::Male)
        ->and($parsed->birthMonth())->toBe(5)
        ->and($parsed->birthDay())->toBe(5)
        ->and($parsed->birthPlaceCode()->value())->toBe('F205')
        ->and($parsed->isOmocodia())->toBeFalse();
});

test('decodes an omocodia variant to the same fields as its canonical form', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F20RU'));

    expect($parsed->surnameCode())->toBe('RSS')
        ->and($parsed->nameCode())->toBe('MRA')
        ->and($parsed->gender())->toBe(Gender::Male)
        ->and($parsed->birthMonth())->toBe(5)
        ->and($parsed->birthDay())->toBe(5)
        ->and($parsed->birthPlaceCode()->value())->toBe('F205')
        ->and($parsed->isOmocodia())->toBeTrue();
});

test('decodes a female code, subtracting the 40-day offset', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E45F205D'));

    expect($parsed->gender())->toBe(Gender::Female)
        ->and($parsed->birthDay())->toBe(5);
});

test('decodes an internationally-birthplaced code correctly', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RBRRHR93L09Z357P'));

    // Position 8 is 'L' -> month 7 (July), per the month-letter table.
    expect($parsed->birthPlaceCode()->value())->toBe('Z357')
        ->and($parsed->birthPlaceCode()->isForeign())->toBeTrue()
        ->and($parsed->birthMonth())->toBe(7)
        ->and($parsed->birthDay())->toBe(9);
});

test('possibleBirthYears() exposes both century candidates for the ambiguous 2-digit year', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    expect($parsed->possibleBirthYears())->toBe([1995, 2095]);
});

test('birthDate() resolves via the default BirthDateResolver when none is injected', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    // 2095 is always in the future relative to when this test runs;
    // only 1995 is ever plausible, so this is deterministic without
    // needing to inject a reference date.
    expect($parsed->birthYear())->toBe(1995)
        ->and($parsed->birthDate())->toEqual(new DateTimeImmutable('1995-05-05'));
});

test('a custom BirthDateResolver is used instead of the default', function () {
    $alwaysPicksTheYoungestCandidate = new class implements BirthDateResolver {
        public function resolve(BirthDateResolutionContext $context): ?DateTimeImmutable
        {
            $candidates = $context->candidates();

            return $candidates === [] ? null : max($candidates);
        }
    };

    $parser = new Parser(new InMemoryBirthPlaceRepository(), $alwaysPicksTheYoungestCandidate);
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    expect($parsed->birthYear())->toBe(2095);
});

test('birthYear() and birthDate() are both null when no candidate is plausible', function () {
    // Reference date 1975-01-01 puts both '95' candidates (1995 and
    // 2095) after it, so neither is plausible.
    $resolver = new DefaultBirthDateResolver(maxAge: 120, referenceDate: new DateTimeImmutable('1975-01-01'));
    $parser = new Parser(new InMemoryBirthPlaceRepository(), $resolver);
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    expect($parsed->birthYear())->toBeNull()
        ->and($parsed->birthDate())->toBeNull()
        ->and($parsed->birthPlace())->toBeNull();
});

test('a leap-day code resolves to the century whose calendar actually has that day', function () {
    $generator = new Generator();
    $parser = new Parser(new InMemoryBirthPlaceRepository());

    $cf = $generator->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('2000-02-29'),
        birthPlace: BirthPlaceCode::from('H501'),
        gender: Gender::Male,
    ));
    $parsed = $parser->parse($cf);

    // Year code '00' is ambiguous between 1900 (not a leap year - Feb
    // 29 doesn't exist) and 2000 (a leap year); only 2000 is a valid
    // calendar date, so it's the sole plausible candidate.
    expect($parsed->possibleBirthYears())->toBe([1900, 2000])
        ->and($parsed->birthDate())->toEqual(new DateTimeImmutable('2000-02-29'));
});

test('birthplace history selects the older candidate through Parser when it is valid only for that date', function () {
    $generator = new Generator();
    $repository = new InMemoryBirthPlaceRepository(
        new DomesticBirthPlace(BirthPlaceCode::from('H501'), 'ROMA', 'RM', '058091', new DateTimeImmutable('1900-01-01'), new DateTimeImmutable('1950-01-01')),
    );
    $resolver = new DefaultBirthDateResolver(maxAge: 120, referenceDate: new DateTimeImmutable('2026-08-09'));
    $parser = new Parser($repository, $resolver);

    // Year code '26' is ambiguous between 1926 and 2026; both are
    // plausible on 2026-08-09, so the birthplace record - valid only
    // through 1950 - breaks the tie in favor of the older candidate.
    $cf = $generator->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1926-01-01'),
        birthPlace: BirthPlaceCode::from('H501'),
        gender: Gender::Male,
    ));

    expect($parser->parse($cf)->birthDate())->toEqual(new DateTimeImmutable('1926-01-01'));
});

test('birthplace history selects the younger candidate through Parser when it is valid only for that date', function () {
    $generator = new Generator();
    $repository = new InMemoryBirthPlaceRepository(
        new DomesticBirthPlace(BirthPlaceCode::from('H501'), 'ROMA', 'RM', '058091', new DateTimeImmutable('2020-01-01')),
    );
    $resolver = new DefaultBirthDateResolver(maxAge: 120, referenceDate: new DateTimeImmutable('2026-08-09'));
    $parser = new Parser($repository, $resolver);

    $cf = $generator->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('2026-01-01'),
        birthPlace: BirthPlaceCode::from('H501'),
        gender: Gender::Male,
    ));

    expect($parser->parse($cf)->birthDate())->toEqual(new DateTimeImmutable('2026-01-01'));
});

test('birthplace history inconclusive through Parser falls back to the younger plausible candidate', function () {
    $generator = new Generator();
    // No record at all for this code: neither candidate resolves to a BirthPlace.
    $repository = new InMemoryBirthPlaceRepository();
    $resolver = new DefaultBirthDateResolver(maxAge: 120, referenceDate: new DateTimeImmutable('2026-08-09'));
    $parser = new Parser($repository, $resolver);

    $cf = $generator->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('2026-01-01'),
        birthPlace: BirthPlaceCode::from('H501'),
        gender: Gender::Male,
    ));

    expect($parser->parse($cf)->birthDate())->toEqual(new DateTimeImmutable('2026-01-01'));
});

test('birthPlace() returns null - not an exception - for an unrecognized code', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    expect($parsed->birthPlace())->toBeNull();
});

test('birthPlace() resolves the era-record valid on the parsed birth date', function () {
    $repository = new InMemoryBirthPlaceRepository(
        abbadiaCerretoUnderMilano(),
        abbadiaCerretoUnderLodi(),
    );
    $parser = new Parser($repository);
    $generator = new Generator();

    $bornUnderMilano = $generator->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1950-01-01'),
        birthPlace: BirthPlaceCode::from('A004'),
        gender: Gender::Male,
    ));
    $bornUnderLodi = $generator->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('2000-01-01'),
        birthPlace: BirthPlaceCode::from('A004'),
        gender: Gender::Male,
    ));

    expect($parser->parse($bornUnderMilano)->birthPlace()->province())->toBe('MI')
        ->and($parser->parse($bornUnderLodi)->birthPlace()->province())->toBe('LO');
});

test('never throws for a nonsense birthplace code - birthPlace() returns null, birthDate() is still valid', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());

    // Every call below runs to completion and returns a real value;
    // if any of them threw, this test would fail with an uncaught
    // exception rather than silently pass.
    $parsed = $parser->parse(CodiceFiscale::from('LNEGLI94D20A000X'));

    expect($parsed->birthPlaceCode()->value())->toBe('A000')
        ->and($parsed->birthPlace())->toBeNull()
        ->and($parsed->birthDate())->toEqual(new DateTimeImmutable('1994-04-20'));
});

test('never throws for a nonexistent calendar date - birthDate() and birthPlace() return null instead', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());

    // Raw day 77 -> female (>40), day 77-40 = 37, not a real day of
    // any month. Every call below runs to completion; if parse() or
    // any accessor threw, this test would fail with an uncaught
    // exception, not silently pass.
    $parsed = $parser->parse(CodiceFiscale::from('LOIMLC71A77F979V'));

    expect($parsed->birthDay())->toBe(37)
        ->and($parsed->birthDate())->toBeNull()
        ->and($parsed->birthPlace())->toBeNull();
});
