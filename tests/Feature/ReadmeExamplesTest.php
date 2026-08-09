<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\PartialPerson;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\Generator;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Matching\Matcher;
use Robertogallea\CodiceFiscale\Omocodia\Omocodia;
use Robertogallea\CodiceFiscale\Parsing\Parser;
use Robertogallea\CodiceFiscale\Validation\Validator;

/**
 * Every value asserted here is the literal value transcribed into
 * README.md's walkthrough - this test exists so that file can never
 * silently drift from the real API's actual behavior.
 */
function seedRoma(): void
{
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);
}

function marioRossiReadme(): Person
{
    return new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1985-04-15'),
        birthPlace: BirthPlaceCode::from('H501'),
        gender: Gender::Male,
    );
}

test('README: generating a code from a Person', function () {
    $cf = (new Generator())->generate(marioRossiReadme());

    expect($cf)->toBeInstanceOf(CodiceFiscale::class)
        ->and($cf->value())->toBe('RSSMRA85D15H501T');
});

test('README: constructing a CodiceFiscale from/tryFrom a string', function () {
    $cf = CodiceFiscale::from('RSSMRA85D15H501T');
    expect($cf->value())->toBe('RSSMRA85D15H501T');

    expect(CodiceFiscale::tryFrom('not-a-real-code'))->toBeNull();
    expect(fn () => CodiceFiscale::from('not-a-real-code'))
        ->toThrow(Robertogallea\CodiceFiscale\Exceptions\InvalidCodiceFiscaleException::class);
});

test('README: parsing a code with the real, container-bound BirthPlaceRepository', function () {
    seedRoma();
    $cf = CodiceFiscale::from('RSSMRA85D15H501T');

    $repository = app(BirthPlaceRepository::class);
    $parsed = (new Parser($repository))->parse($cf);

    expect($parsed->surnameCode())->toBe('RSS')
        ->and($parsed->nameCode())->toBe('MRA')
        ->and($parsed->gender())->toBe(Gender::Male)
        ->and($parsed->birthDate()?->format('Y-m-d'))->toBe('1985-04-15')
        ->and($parsed->birthPlaceCode()->value())->toBe('H501')
        ->and($parsed->birthPlace()?->name())->toBe('ROMA')
        ->and($parsed->isOmocodia())->toBeFalse();
});

test('README: validating a code end to end', function () {
    seedRoma();
    $validator = new Validator(app(BirthPlaceRepository::class));

    $result = $validator->validate('RSSMRA85D15H501T');

    expect($result->valid())->toBeTrue()
        ->and($result->errors())->toBe([]);

    $badResult = $validator->validate('not-a-real-code');
    expect($badResult->valid())->toBeFalse()
        ->and($badResult->errors())->toBe([Robertogallea\CodiceFiscale\Enums\ValidationError::InvalidFormat]);
});

test('README: matching a code against a Person/PartialPerson', function () {
    seedRoma();
    $cf = (new Generator())->generate(marioRossiReadme());
    $matcher = new Matcher(new Parser(app(BirthPlaceRepository::class)));

    $fullMatch = $matcher->match($cf, marioRossiReadme());
    expect($fullMatch->matches())->toBeTrue()
        ->and($fullMatch->skipped())->toBe([]);

    $partial = $matcher->match($cf, new PartialPerson(firstName: 'Mario', gender: Gender::Female));
    expect($partial->matches())->toBeFalse()
        ->and($partial->mismatched())->toBe([Robertogallea\CodiceFiscale\Enums\PersonField::Gender])
        ->and($partial->matched())->toBe([Robertogallea\CodiceFiscale\Enums\PersonField::FirstName]);
});

test('README: omocodia canonical()/variants()/level()', function () {
    $cf = CodiceFiscale::from('RSSMRA85D15H501T');
    $omocodia = new Omocodia();

    expect($omocodia->canonical($cf)->value())->toBe('RSSMRA85D15H501T')
        ->and($omocodia->level($cf))->toBe(0)
        ->and(iterator_to_array($omocodia->variants($cf)))->toHaveCount(128);

    $variants = iterator_to_array($omocodia->variants($cf));
    $oneSubstitution = current(array_filter($variants, fn ($variant) => $omocodia->level($variant) === 1));

    expect($oneSubstitution->isEquivalentTo($cf))->toBeTrue()
        ->and($oneSubstitution->value())->toBe('RSSMRA85D15H50ML');
});

test('README: BirthPlaceRepository find()/existedEver(), domestic and foreign', function () {
    seedRoma();
    ForeignCountry::create([
        'code' => 'Z404', 'name' => "STATI UNITI D'AMERICA", 'country_code' => 'USA',
        'valid_from' => '1900-01-01', 'valid_to' => null,
    ]);

    $repository = app(BirthPlaceRepository::class);

    $roma = $repository->find(BirthPlaceCode::from('H501'));
    expect($roma)->toBeInstanceOf(Robertogallea\CodiceFiscale\Data\DomesticBirthPlace::class)
        ->and($roma->name())->toBe('ROMA')
        ->and($roma->province())->toBe('RM');

    $usa = $repository->find(BirthPlaceCode::from('Z404'));
    expect($usa)->toBeInstanceOf(Robertogallea\CodiceFiscale\Data\ForeignBirthPlace::class)
        ->and($usa->country()->value())->toBe('USA');

    expect($repository->existedEver(BirthPlaceCode::from('A999')))->toBeFalse();
});
