<?php

use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

test('uppercases a plain name', function () {
    expect((new PlaceNameNormalizer())->normalize('roma'))->toBe('ROMA');
});

test('transliterates accented letters instead of dropping them', function () {
    expect((new PlaceNameNormalizer())->normalize('Città di Castello'))->toBe('CITTA DI CASTELLO');
});

test('preserves apostrophes, unlike the person-name normalizer', function () {
    expect((new PlaceNameNormalizer())->normalize("L'Aquila"))->toBe("L'AQUILA");
});

test('preserves word boundaries, unlike the person-name normalizer', function () {
    expect((new PlaceNameNormalizer())->normalize('Reggio Emilia'))->toBe('REGGIO EMILIA');
});

test('collapses extra whitespace instead of dropping it', function () {
    expect((new PlaceNameNormalizer())->normalize('  Reggio   Emilia  '))->toBe('REGGIO EMILIA');
});

test('strips punctuation that is not a word or apostrophe boundary', function () {
    expect((new PlaceNameNormalizer())->normalize("Stati Uniti d'America (USA)"))->toBe("STATI UNITI D'AMERICA USA");
});

test('normalizes other accented real place names', function (string $input, string $expected) {
    expect((new PlaceNameNormalizer())->normalize($input))->toBe($expected);
})->with([
    'foreign country with accent' => ["Cote d'Ivoire", "COTE D'IVOIRE"],
    'already uppercase with accent' => ['ÀNGERA', 'ANGERA'],
]);
