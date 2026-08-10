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

    $messages = implode(' ', $result->errors()->get('fiscal_code'));

    expect($result->fails())->toBeTrue()
        ->and($result->errors()->get('fiscal_code'))->toHaveCount(2)
        ->and($messages)->toContain('nome')
        ->and($messages)->toContain('sesso');
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

test('CodiceFiscaleRule::make() reports the invalid_format message, translated per locale', function () {
    $validate = fn () => LaravelValidator::make(
        ['fiscal_code' => 'not-a-real-code'],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('The fiscal_code is not a validly formatted codice fiscale.');

    app()->setLocale('it');

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('Il campo fiscal_code non è un codice fiscale correttamente formattato.');
});

test('CodiceFiscaleRule::make() reports the invalid_checksum message, translated per locale', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());
    $tampered = substr($cf->value(), 0, -1).($cf->value()[-1] === 'Z' ? 'A' : 'Z');

    $validate = fn () => LaravelValidator::make(
        ['fiscal_code' => $tampered],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('The fiscal_code has an invalid check character.');

    app()->setLocale('it');

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('Il campo fiscal_code presenta un carattere di controllo non valido.');
});

test('CodiceFiscaleRule::make() reports the invalid_date message, translated per locale', function () {
    // RSSMRA95E32F205U: Mario Rossi, day 32 of month E (May) 1995 -
    // structurally valid and checksum-valid, but no such calendar
    // date exists. F205 (Palermo) is seeded so only InvalidDate fires.
    seedPalermo();

    $validate = fn () => LaravelValidator::make(
        ['fiscal_code' => 'RSSMRA95E32F205U'],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('The fiscal_code encodes a date that does not exist.');

    app()->setLocale('it');

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('Il campo fiscal_code codifica una data inesistente.');
});

test('CodiceFiscaleRule::make() reports the unknown_birth_place message, translated per locale', function () {
    // Palermo is never seeded here, so a structurally/checksum-valid
    // code encoding it must still fail on the semantics tier.
    $cf = (new Generator())->generate(marioRossi());

    $validate = fn () => LaravelValidator::make(
        ['fiscal_code' => $cf->value()],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('The fiscal_code references a birthplace that is not recognized.');

    app()->setLocale('it');

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('Il campo fiscal_code fa riferimento a un luogo di nascita non riconosciuto.');
});

test('CodiceFiscaleRule::make() reports the birth_place_not_valid_on_date message, translated per locale', function () {
    // A004 (Abbadia Cerreto) only seeded from its Lodi era onward
    // (1992-04-16); a code born well before that is a recognized
    // birthplace that just wasn't valid yet on the encoded date.
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'province' => 'LO',
        'istat_code' => '098001', 'valid_from' => '1992-04-16', 'valid_to' => null,
    ]);

    $cf = (new Generator())->generate(new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1950-01-01'),
        birthPlace: BirthPlaceCode::from('A004'),
        gender: Gender::Male,
    ));

    $validate = fn () => LaravelValidator::make(
        ['fiscal_code' => $cf->value()],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('The fiscal_code references a birthplace that was not valid on the encoded date.');

    app()->setLocale('it');

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('Il campo fiscal_code fa riferimento a un luogo di nascita non valido alla data codificata.');
});

test('CodiceFiscaleRule::make() reports every distinct error when checksum and semantics both fail', function () {
    // Not seeded (unknown birthplace) *and* checksum-tampered: the two
    // tiers run independently, so both errors surface together.
    $cf = (new Generator())->generate(marioRossi());
    $tampered = substr($cf->value(), 0, -1).($cf->value()[-1] === 'Z' ? 'A' : 'Z');

    $result = LaravelValidator::make(
        ['fiscal_code' => $tampered],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    expect($result->errors()->get('fiscal_code'))->toBe([
        'The fiscal_code has an invalid check character.',
        'The fiscal_code references a birthplace that is not recognized.',
    ]);
});

test('->matching() reports a translated, field-naming message for a single mismatch', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $validate = fn () => LaravelValidator::make(
        ['fiscal_code' => $cf->value(), 'sesso' => 'F'],
        ['fiscal_code' => [CodiceFiscaleRule::make()->matching(gender: 'sesso')]],
    );

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('The fiscal_code does not match the provided sesso.');

    app()->setLocale('it');

    expect($validate()->errors()->first('fiscal_code'))
        ->toBe('Il campo fiscal_code non corrisponde al valore fornito per sesso.');
});

test('->matching() reports one translated, field-naming message per mismatched field', function () {
    seedPalermo();
    $cf = (new Generator())->generate(marioRossi());

    $validate = fn () => LaravelValidator::make(
        ['fiscal_code' => $cf->value(), 'nome' => 'Luigi', 'sesso' => 'F'],
        ['fiscal_code' => [CodiceFiscaleRule::make()->matching(firstName: 'nome', gender: 'sesso')]],
    );

    expect($validate()->errors()->get('fiscal_code'))->toBe([
        'The fiscal_code does not match the provided nome.',
        'The fiscal_code does not match the provided sesso.',
    ]);

    app()->setLocale('it');

    expect($validate()->errors()->get('fiscal_code'))->toBe([
        'Il campo fiscal_code non corrisponde al valore fornito per nome.',
        'Il campo fiscal_code non corrisponde al valore fornito per sesso.',
    ]);
});

test('the codice_fiscale string-rule alias produces the same translated message as CodiceFiscaleRule for a representative failure', function () {
    $classBased = LaravelValidator::make(
        ['fiscal_code' => 'not-a-real-code'],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    $stringAlias = LaravelValidator::make(
        ['fiscal_code' => 'not-a-real-code'],
        ['fiscal_code' => 'codice_fiscale'],
    );

    expect($stringAlias->errors()->first('fiscal_code'))
        ->toBe($classBased->errors()->first('fiscal_code'));

    app()->setLocale('it');

    $classBasedIt = LaravelValidator::make(
        ['fiscal_code' => 'not-a-real-code'],
        ['fiscal_code' => [CodiceFiscaleRule::make()]],
    );

    $stringAliasIt = LaravelValidator::make(
        ['fiscal_code' => 'not-a-real-code'],
        ['fiscal_code' => 'codice_fiscale'],
    );

    expect($stringAliasIt->errors()->first('fiscal_code'))
        ->toBe($classBasedIt->errors()->first('fiscal_code'));
});
