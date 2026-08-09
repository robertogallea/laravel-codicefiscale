<?php

use Robertogallea\CodiceFiscale\Generation\ItalianNameNormalizer;

test('uppercases a plain name', function () {
    expect((new ItalianNameNormalizer())->normalize('mario'))->toBe('MARIO');
});

test('transliterates accented letters instead of dropping them', function () {
    expect((new ItalianNameNormalizer())->normalize('José'))->toBe('JOSE');
});

test('strips apostrophes', function () {
    expect((new ItalianNameNormalizer())->normalize("D'Angelo"))->toBe('DANGELO');
});

test('strips extra whitespace', function () {
    expect((new ItalianNameNormalizer())->normalize('  Mario  Luigi  '))->toBe('MARIOLUIGI');
});

test('normalizes other accented and punctuated real names', function (string $input, string $expected) {
    expect((new ItalianNameNormalizer())->normalize($input))->toBe($expected);
})->with([
    'ç and ñ' => ['François Núñez', 'FRANCOISNUNEZ'],
    'hyphenated' => ['Anne-Marie', 'ANNEMARIE'],
    'already uppercase with accent' => ['ÀNGELA', 'ANGELA'],
]);
