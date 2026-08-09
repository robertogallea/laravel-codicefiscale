<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Contracts\CenturyResolver;
use Robertogallea\CodiceFiscale\Contracts\NameNormalizer;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Data\PartialPerson;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Enums\PersonField;
use Robertogallea\CodiceFiscale\Enums\ValidationError;
use Robertogallea\CodiceFiscale\Exceptions\InvalidCodiceFiscaleException;
use Robertogallea\CodiceFiscale\Generation\Generator;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Matching\Matcher;
use Robertogallea\CodiceFiscale\Omocodia\Omocodia;
use Robertogallea\CodiceFiscale\Parsing\Century\AgeBasedCenturyResolver;
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
        ->toThrow(InvalidCodiceFiscaleException::class);
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
        ->and($badResult->errors())->toBe([ValidationError::InvalidFormat]);
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
        ->and($partial->mismatched())->toBe([PersonField::Gender])
        ->and($partial->matched())->toBe([PersonField::FirstName]);
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
    expect($roma)->toBeInstanceOf(DomesticBirthPlace::class)
        ->and($roma->name())->toBe('ROMA')
        ->and($roma->province())->toBe('RM');

    $usa = $repository->find(BirthPlaceCode::from('Z404'));
    expect($usa)->toBeInstanceOf(ForeignBirthPlace::class)
        ->and($usa->country()->value())->toBe('USA');

    expect($repository->existedEver(BirthPlaceCode::from('A999')))->toBeFalse();
});

test('README: swapping NameNormalizer changes how Generator encodes a name', function () {
    $reversingNormalizer = new class implements NameNormalizer {
        public function normalize(string $name): string
        {
            return strtoupper(strrev($name));
        }
    };

    $default = (new Generator())->generate(marioRossiReadme());
    $customized = (new Generator(nameNormalizer: $reversingNormalizer))->generate(marioRossiReadme());

    expect($customized->value())->not->toBe($default->value());
});

test('README: supplying a custom CenturyResolver changes which century an ambiguous two-digit year resolves to', function () {
    $default = new AgeBasedCenturyResolver(maxAge: 120);

    $always1900s = new class implements CenturyResolver {
        public function resolve(array $possibleYears): int
        {
            return min($possibleYears);
        }
    };

    // Code '02': 1902 is 124 years before 2026 (over the default's
    // 120-year plausibility window), so the built-in default falls
    // back to 2002 - the youngest plausible reading. A domain that
    // knows better (e.g. "this system only has customers born after
    // 1970... but definitely not after 2000") can override that guess.
    expect($default->resolve([1902, 2002]))->toBe(2002)
        ->and($always1900s->resolve([1902, 2002]))->toBe(1902);
});
