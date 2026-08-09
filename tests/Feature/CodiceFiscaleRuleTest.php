<?php

use Illuminate\Support\Facades\Validator as LaravelValidator;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\Generator;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Laravel\Rules\CodiceFiscaleRule;

function seedPalermo(): void
{
    Municipality::create([
        'code' => 'F205', 'name' => 'PALERMO', 'province' => 'PA',
        'istat_code' => '082053', 'valid_from' => '1861-01-01', 'valid_to' => null,
    ]);
}

function marioRossi(): Person
{
    return new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'),
        gender: Gender::Male,
    );
}

test('CodiceFiscaleRule::make() alone passes a structurally, checksum, and semantically valid code', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        ['fiscal_code' => $cf->value()],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($result->passes())->toBeTrue();
});

test('CodiceFiscaleRule::make() alone fails a structurally malformed value', function () {
    $result = LaravelValidator::make(
        ['fiscal_code' => 'not-a-real-code'],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($result->fails())->toBeTrue();
});

test('CodiceFiscaleRule::make() alone fails a checksum-invalid value', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());
    $tampered = substr($cf->value(), 0, -1).($cf->value()[-1] === 'Z' ? 'A' : 'Z');

    $result = LaravelValidator::make(
        ['fiscal_code' => $tampered],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($result->fails())->toBeTrue();
});

test('CodiceFiscaleRule::make() alone fails a semantically-invalid value (unrecognized birthplace code)', function () {
    // Palermo is never seeded here, so a structurally/checksum-valid
    // code encoding it must still fail on the semantics tier.
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        ['fiscal_code' => $cf->value()],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($result->fails())->toBeTrue();
});

test('->matching() passes when every supplied field matches the code', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        [
            'fiscal_code' => $cf->value(),
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'data_nascita' => '1995-05-05',
            'luogo_nascita' => 'F205',
            'sesso' => 'M',
        ],
        ['fiscal_code' => [CodiceFiscaleRule::make()->matching(
            firstName: 'nome',
            lastName: 'cognome',
            birthDate: 'data_nascita',
            birthPlace: 'luogo_nascita',
            gender: 'sesso',
        )]],
    );

    expect($result->passes())->toBeTrue();
});

test('->matching() fails on a mismatched field and reports which field failed', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        [
            'fiscal_code' => $cf->value(),
            'sesso' => 'F',
        ],
        ['fiscal_code' => [CodiceFiscaleRule::make()->matching(gender: 'sesso')]],
    );

    expect($result->fails())->toBeTrue()
        ->and($result->errors()->first('fiscal_code'))->toContain('sesso');
});

test('->matching() with a field absent from the request data is treated as skipped, not a mismatch', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        ['fiscal_code' => $cf->value()],
        ['fiscal_code' => [CodiceFiscaleRule::make()->matching(gender: 'sesso')]],
    );

    expect($result->passes())->toBeTrue();
});

test('->matching() reports every mismatched field, not just the first', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        [
            'fiscal_code' => $cf->value(),
            'nome' => 'Luigi',
            'sesso' => 'F',
        ],
        ['fiscal_code' => [CodiceFiscaleRule::make()->matching(firstName: 'nome', gender: 'sesso')]],
    );

    expect($result->fails())->toBeTrue()
        ->and($result->errors()->first('fiscal_code'))->toContain('nome')
        ->and($result->errors()->first('fiscal_code'))->toContain('sesso');
});

test('the codice_fiscale string-rule alias passes a valid code without the fluent builder', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        ['fiscal_code' => $cf->value()],
        ['fiscal_code' => 'codice_fiscale'],
    );

    expect($result->passes())->toBeTrue();
});

test('the codice_fiscale string-rule alias fails a structurally malformed value', function () {
    $result = LaravelValidator::make(
        ['fiscal_code' => 'not-a-real-code'],
        ['fiscal_code' => 'codice_fiscale'],
    );

    expect($result->fails())->toBeTrue();
});

test('the codice_fiscale string-rule alias fails a semantically-invalid value (unrecognized birthplace code)', function () {
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        ['fiscal_code' => $cf->value()],
        ['fiscal_code' => 'codice_fiscale'],
    );

    expect($result->fails())->toBeTrue();
});

test('->matching() treats an unparseable gender/birthplace value as skipped, not a mismatch', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $result = LaravelValidator::make(
        [
            'fiscal_code' => $cf->value(),
            'sesso' => 'not-a-gender',
            'luogo_nascita' => 'not-a-code',
        ],
        ['fiscal_code' => [CodiceFiscaleRule::make()->matching(gender: 'sesso', birthPlace: 'luogo_nascita')]],
    );

    expect($result->passes())->toBeTrue();
});
