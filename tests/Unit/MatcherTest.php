<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\PartialPerson;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Enums\PersonField;
use Robertogallea\CodiceFiscale\Generation\Generator;
use Robertogallea\CodiceFiscale\Matching\Matcher;
use Robertogallea\CodiceFiscale\Parsing\Parser;
use Tests\Support\InMemoryBirthPlaceRepository;

test('matching against the exact Person a code was generated from reports every field matched', function () {
    $person = new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'),
        gender: Gender::Male,
    );
    $cf = (new Generator())->generate($person);

    $matcher = new Matcher(new Parser(new InMemoryBirthPlaceRepository()));
    $result = $matcher->match($cf, $person);

    expect($result->matches())->toBeTrue()
        ->and($result->matched())->toEqualCanonicalizing([
            PersonField::FirstName,
            PersonField::LastName,
            PersonField::BirthDate,
            PersonField::BirthPlace,
            PersonField::Gender,
        ])
        ->and($result->mismatched())->toBe([])
        ->and($result->skipped())->toBe([]);
});

test('matching against a PartialPerson with only firstName/lastName set reports the rest as skipped, not matched', function () {
    $cf = (new Generator())->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'),
        gender: Gender::Male,
    ));

    $partial = new PartialPerson(firstName: 'Mario', lastName: 'Rossi');

    $matcher = new Matcher(new Parser(new InMemoryBirthPlaceRepository()));
    $result = $matcher->match($cf, $partial);

    expect($result->matches())->toBeTrue()
        ->and($result->matched())->toEqualCanonicalizing([PersonField::FirstName, PersonField::LastName])
        ->and($result->mismatched())->toBe([])
        ->and($result->skipped())->toEqualCanonicalizing([
            PersonField::BirthDate,
            PersonField::BirthPlace,
            PersonField::Gender,
        ]);
});

test('the three states are distinguishable: fully verified vs. partial-nothing-failed vs. explicit mismatch', function () {
    $cf = (new Generator())->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'),
        gender: Gender::Male,
    ));
    $matcher = new Matcher(new Parser(new InMemoryBirthPlaceRepository()));

    $fullyVerified = $matcher->match($cf, new Person(
        firstName: 'Mario', lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'), gender: Gender::Male,
    ));
    expect($fullyVerified->matches())->toBeTrue()->and($fullyVerified->skipped())->toBe([]);

    $partialNothingFailed = $matcher->match($cf, new PartialPerson(firstName: 'Mario'));
    expect($partialNothingFailed->matches())->toBeTrue()->and($partialNothingFailed->skipped())->not->toBe([]);

    $explicitMismatch = $matcher->match($cf, new PartialPerson(firstName: 'Luigi'));
    expect($explicitMismatch->matches())->toBeFalse();
});

test('changing a single field before matching reports exactly that field as mismatched, the rest matched', function () {
    $generatedFrom = new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'),
        gender: Gender::Male,
    );
    $cf = (new Generator())->generate($generatedFrom);

    $withDifferentGender = new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'),
        gender: Gender::Female,
    );

    $matcher = new Matcher(new Parser(new InMemoryBirthPlaceRepository()));
    $result = $matcher->match($cf, $withDifferentGender);

    expect($result->matches())->toBeFalse()
        ->and($result->mismatched())->toBe([PersonField::Gender])
        ->and($result->matched())->toEqualCanonicalizing([
            PersonField::FirstName,
            PersonField::LastName,
            PersonField::BirthDate,
            PersonField::BirthPlace,
        ])
        ->and($result->skipped())->toBe([]);
});

test('birthDate mismatches (not skips or crashes) when the code encodes an undecodable date', function () {
    // Day 77 -> female (>40), day 37: not a real day of any month, so
    // ParsedCodiceFiscale::birthDate() is null. A caller-provided
    // birthDate can never be a real match against "no date at all".
    $cf = CodiceFiscale::from('LOIMLC71A77F979V');

    $matcher = new Matcher(new Parser(new InMemoryBirthPlaceRepository()));
    $result = $matcher->match($cf, new PartialPerson(birthDate: new DateTimeImmutable('1971-01-01')));

    expect($result->matches())->toBeFalse()
        ->and($result->mismatched())->toBe([PersonField::BirthDate]);
});
