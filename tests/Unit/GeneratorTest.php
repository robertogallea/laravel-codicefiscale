<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\Generator;

test('generates a checksum-correct code for a known fixture', function () {
    $person = new Person(
        firstName: 'Mario',
        lastName: 'Rossi',
        birthDate: new DateTimeImmutable('1995-05-05'),
        birthPlace: BirthPlaceCode::from('F205'),
        gender: Gender::Male,
    );

    expect((new Generator())->generate($person)->value())->toBe('RSSMRA95E05F205Z');
});

test('generates other real fixtures correctly', function (string $first, string $last, string $date, string $place, Gender $gender, string $expected) {
    $person = new Person(
        firstName: $first,
        lastName: $last,
        birthDate: new DateTimeImmutable($date),
        birthPlace: BirthPlaceCode::from($place),
        gender: $gender,
    );

    expect((new Generator())->generate($person)->value())->toBe($expected);
})->with([
    'short first name padded with X' => ['Ma', 'Rossi', '1995-05-05', 'F205', Gender::Male, 'RSSMAX95E05F205P'],
    'both names need padding' => ['ASH', 'OS', '1990-01-01', 'F205', Gender::Male, 'SOXSHA90A01F205B'],
    'surname needs padding, name is exact' => ['MARIO', 'OM', '1990-01-01', 'F205', Gender::Male, 'MOXMRA90A01F205S'],
    'first name has zero consonants' => ['IO', 'ROSSI', '1990-01-01', 'F205', Gender::Male, 'RSSIOX90A01F205V'],
    'female (day + 40)' => ['Mario', 'Rossi', '1995-05-05', 'F205', Gender::Female, 'RSSMRA95E45F205D'],
]);

test('generates a correct code from accented, apostrophe-containing names without pre-normalizing', function () {
    $person = new Person(
        firstName: 'José',
        lastName: "D'Angelo",
        birthDate: new DateTimeImmutable('1985-08-01'),
        birthPlace: BirthPlaceCode::from('H501'),
        gender: Gender::Male,
    );

    // DANGELO -> consonants D,N,G (surname rule: first 3, no skip) -> DNG
    // JOSE -> consonants J,S (<=3) -> JS + vowel O -> JSO
    // 1985-08-01 male -> 85 M 01; Roma -> H501
    expect((new Generator())->generate($person)->value())->toBe('DNGJSO85M01H501C');
});
