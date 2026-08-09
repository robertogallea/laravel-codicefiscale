<?php

use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\DateEncoder;

test('encodes a male birth date as year, month letter, and day', function () {
    $encoder = new DateEncoder();

    expect($encoder->encode(new DateTimeImmutable('1985-08-01'), Gender::Male))->toBe('85M01');
});

test('adds 40 to the day for a female birth date', function () {
    $encoder = new DateEncoder();

    expect($encoder->encode(new DateTimeImmutable('1995-05-05'), Gender::Female))->toBe('95E45');
});

test('encodes other real fixtures correctly', function (string $date, Gender $gender, string $expected) {
    expect((new DateEncoder())->encode(new DateTimeImmutable($date), $gender))->toBe($expected);
})->with([
    'January, single-digit day' => ['1990-01-01', Gender::Male, '90A01'],
    'December' => ['1971-12-05', Gender::Male, '71T05'],
    'June (letter H, not F)' => ['2000-06-15', Gender::Male, '00H15'],
]);
