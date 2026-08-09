<?php

use Robertogallea\CodiceFiscale\Generation\NameEncoder;

test('surnameCode() takes the first three consonants', function () {
    expect((new NameEncoder())->surnameCode('ROSSI'))->toBe('RSS');
});

test('surnameCode() real fixtures, including padding with vowels and X', function (string $surname, string $expected) {
    expect((new NameEncoder())->surnameCode($surname))->toBe($expected);
})->with([
    'one consonant, needs a vowel and an X' => ['OS', 'SOX'],
    'one consonant, needs a vowel, none left after' => ['OM', 'MOX'],
    'no consonants at all' => ['IO', 'IOX'],
]);

test('nameCode() takes all consonants when there are three or fewer', function (string $name, string $expected) {
    expect((new NameEncoder())->nameCode($name))->toBe($expected);
})->with([
    'exactly three consonants' => ['MARIO', 'MRA'],
    'two consonants, padded with a vowel' => ['ASH', 'SHA'],
    'no consonants, padded with vowels then X' => ['IO', 'IOX'],
    'short name, padded entirely with X' => ['MA', 'MAX'],
]);

test('nameCode() skips the 2nd consonant when there are four or more, using the 1st/3rd/4th', function () {
    // ALBERTO -> consonants L,B,R,T (4) -> skip the 2nd (B) -> L,R,T
    expect((new NameEncoder())->nameCode('ALBERTO'))->toBe('LRT');
});
