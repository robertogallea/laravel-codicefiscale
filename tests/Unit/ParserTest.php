<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\CenturyResolver;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\Generator;
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

test('birthDate() resolves via the default AgeBasedCenturyResolver when none is injected', function () {
    $parser = new Parser(new InMemoryBirthPlaceRepository());
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    // 2095 is always in the future relative to when this test runs;
    // only 1995 is ever plausible, so this is deterministic without
    // needing to inject a reference date.
    expect($parsed->birthYear())->toBe(1995)
        ->and($parsed->birthDate())->toEqual(new DateTimeImmutable('1995-05-05'));
});

test('a custom CenturyResolver is used instead of the default', function () {
    $alwaysPicksTheFutureCentury = new class implements CenturyResolver {
        public function resolve(array $possibleYears): int
        {
            return max($possibleYears);
        }
    };

    $parser = new Parser(new InMemoryBirthPlaceRepository(), $alwaysPicksTheFutureCentury);
    $parsed = $parser->parse(CodiceFiscale::from('RSSMRA95E05F205Z'));

    expect($parsed->birthYear())->toBe(2095);
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

test('never throws for structurally-fine-but-nonsense data - only CodiceFiscale::from() rejects malformed input', function (string $code) {
    $parser = new Parser(new InMemoryBirthPlaceRepository());

    $parsed = null;
    expect(function () use ($parser, $code, &$parsed) {
        $parsed = $parser->parse(CodiceFiscale::from($code));
    })->not->toThrow(Throwable::class);

    // Fully exercise every method, including the ones that touch the
    // repository or build a DateTimeImmutable, not just parse() itself.
    expect(fn () => [
        $parsed->birthPlaceCode(),
        $parsed->birthPlace(),
        $parsed->birthDate(),
        $parsed->birthYear(),
    ])->not->toThrow(Throwable::class);
})->with([
    'nonsense birthplace code' => ['LNEGLI94D20A000X'],
    'nonexistent calendar date (day 77)' => ['LOIMLC71A77F979V'],
]);
