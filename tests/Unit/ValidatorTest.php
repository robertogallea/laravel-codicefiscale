<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Enums\ValidationError;
use Robertogallea\CodiceFiscale\Generation\Generator;
use Robertogallea\CodiceFiscale\Validation\Validator;
use Tests\Support\InMemoryBirthPlaceRepository;

test('validateFormat() is valid for a structurally well-formed string', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    $result = $validator->validateFormat('RSSMRA95E05F205Z');

    expect($result->valid())->toBeTrue()
        ->and($result->errors())->toBe([]);
});

test('validateFormat() reports InvalidFormat for a malformed string', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    $result = $validator->validateFormat('ABC');

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::InvalidFormat]);
});

test('validateChecksum() is valid for a correct check character', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    $result = $validator->validateChecksum(CodiceFiscale::from('RSSMRA95E05F205Z'));

    expect($result->valid())->toBeTrue();
});

test('validateChecksum() reports InvalidChecksum for a wrong check character', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    // RSSMRA95E05F205A has an incorrect check character (the real one is Z),
    // but it's still structurally well-formed so CodiceFiscale::from() accepts it.
    $result = $validator->validateChecksum(CodiceFiscale::from('RSSMRA95E05F205A'));

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::InvalidChecksum]);
});

test('validateSemantics() is valid for a real date and a recognized birthplace', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi());
    $validator = new Validator($repository);

    $bornUnderLodi = (new Generator())->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('2000-01-01'),
        birthPlace: BirthPlaceCode::from('A004'),
        gender: Gender::Male,
    ));

    expect($validator->validateSemantics($bornUnderLodi)->valid())->toBeTrue();
});

test('validateSemantics() reports InvalidDate for a nonexistent calendar date', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    // Day 77 -> female (>40), day 37: not a real day of any month.
    // This fixture's birthplace code (F979) also isn't recognized by
    // the empty repository, so UnknownBirthPlace legitimately appears
    // alongside it - both independent errors surface in one pass.
    $result = $validator->validateSemantics(CodiceFiscale::from('LOIMLC71A77F979V'));

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::InvalidDate, ValidationError::UnknownBirthPlace]);
});

test('validateSemantics() reports UnknownBirthPlace for a code that never existed', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    $result = $validator->validateSemantics(CodiceFiscale::from('LNEGLI94D20A000X'));

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::UnknownBirthPlace]);
});

test('validateSemantics() reports BirthPlaceNotValidOnDate - distinct from UnknownBirthPlace - for a code that existed, just not on this date', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi());
    $validator = new Validator($repository);

    // A004 exists (under Lodi, from 1992-04-16), but this person is
    // encoded as born in 1950 - before that era-record's validFrom.
    $bornBeforeLodiEra = (new Generator())->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1950-01-01'),
        birthPlace: BirthPlaceCode::from('A004'),
        gender: Gender::Male,
    ));

    $result = $validator->validateSemantics($bornBeforeLodiEra);

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::BirthPlaceNotValidOnDate]);
});

test('validate() is valid for a fully correct code', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi());
    $validator = new Validator($repository);

    $valid = (new Generator())->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('2000-01-01'),
        birthPlace: BirthPlaceCode::from('A004'),
        gender: Gender::Male,
    ));

    expect($validator->validate($valid->value())->valid())->toBeTrue();
});

test('validate() reports only format errors for malformed input - checksum/semantics are never attempted', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    // Too short to safely slice for checksum/semantic checks; if either
    // were attempted regardless, this would produce spurious errors
    // (or a crash) instead of exactly one clean InvalidFormat.
    $result = $validator->validate('ABC');

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::InvalidFormat]);
});

test('validate() reports checksum and semantic errors together for a well-formed but doubly-wrong code', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository());

    // RSSMRA95E05F205A: well-formed, wrong checksum (real one is Z),
    // and F205 (Milano) is unrecognized by the empty repository.
    $result = $validator->validate('RSSMRA95E05F205A');

    expect($result->valid())->toBeFalse()
        ->and($result->errors())->toBe([ValidationError::InvalidChecksum, ValidationError::UnknownBirthPlace]);
});

test("validator's public API has no method accepting a Person", function () {
    $methods = (new ReflectionClass(Validator::class))->getMethods(ReflectionMethod::IS_PUBLIC);

    foreach ($methods as $method) {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

            expect($typeName)->not->toBe(Person::class);
        }
    }
});
