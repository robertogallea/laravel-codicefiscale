<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Generation\Checksum;
use Robertogallea\CodiceFiscale\Omocodia\Omocodia;

test('canonical() leaves an already-canonical code unchanged', function () {
    $cf = CodiceFiscale::from('RSSMRA95E05F205Z');

    expect((new Omocodia())->canonical($cf)->value())->toBe('RSSMRA95E05F205Z');
});

test('canonical() reverses a single omocodia substitution back to its digit', function () {
    $variant = CodiceFiscale::from('RSSMRA95E05F20RU');

    expect((new Omocodia())->canonical($variant)->value())->toBe('RSSMRA95E05F205Z');
});

test('canonical() reverses every substitution level back to the same base code', function (string $variant) {
    expect((new Omocodia())->canonical(CodiceFiscale::from($variant))->value())->toBe('RSSMRA95E05F205Z');
})->with([
    'level 1' => ['RSSMRA95E05F20RU'],
    'level 2' => ['RSSMRA95E05F2LRF'],
    'level 3' => ['RSSMRA95E05FNLRU'],
    'level 4' => ['RSSMRA95E0RFNLRP'],
    'level 5' => ['RSSMRA95ELRFNLRA'],
    'level 6' => ['RSSMRA9RELRFNLRM'],
    'level 7 (all positions substituted)' => ['RSSMRAVRELRFNLRB'],
]);

test('level() counts how many of the 7 eligible positions are letter-substituted', function (string $code, int $expectedLevel) {
    expect((new Omocodia())->level(CodiceFiscale::from($code)))->toBe($expectedLevel);
})->with([
    'level 0 (canonical)' => ['RSSMRA95E05F205Z', 0],
    'level 1' => ['RSSMRA95E05F20RU', 1],
    'level 2' => ['RSSMRA95E05F2LRF', 2],
    'level 3' => ['RSSMRA95E05FNLRU', 3],
    'level 4' => ['RSSMRA95E0RFNLRP', 4],
    'level 5' => ['RSSMRA95ELRFNLRA', 5],
    'level 6' => ['RSSMRA9RELRFNLRM', 6],
    'level 7' => ['RSSMRAVRELRFNLRB', 7],
]);

test('variants() returns exactly 128 items, every one checksum-correct', function () {
    $canonical = CodiceFiscale::from('RSSMRA95E05F205Z');
    $checksum = new Checksum();

    $variants = iterator_to_array((new Omocodia())->variants($canonical));

    expect($variants)->toHaveCount(128);

    foreach ($variants as $variant) {
        expect($checksum->verify($variant->value()))->toBeTrue();
    }
});

test('variants() are all distinct', function () {
    $canonical = CodiceFiscale::from('RSSMRA95E05F205Z');

    $values = array_map(
        fn ($variant) => $variant->value(),
        iterator_to_array((new Omocodia())->variants($canonical))
    );

    expect(array_unique($values))->toHaveCount(128);
});

test('variants() includes the canonical form itself and the known real omocodia fixtures', function () {
    $canonical = CodiceFiscale::from('RSSMRA95E05F205Z');

    $values = array_map(
        fn ($variant) => $variant->value(),
        iterator_to_array((new Omocodia())->variants($canonical))
    );

    expect($values)->toContain('RSSMRA95E05F205Z')
        ->toContain('RSSMRA95E05F20RU')
        ->toContain('RSSMRAVRELRFNLRB');
});

test('canonical() of every variant returns the original canonical code', function () {
    $canonical = CodiceFiscale::from('RSSMRA95E05F205Z');
    $omocodia = new Omocodia();

    foreach ($omocodia->variants($canonical) as $variant) {
        expect($omocodia->canonical($variant)->value())->toBe('RSSMRA95E05F205Z');
    }
});

test('variants() called on a non-canonical variant still enumerates the same 128-member set', function () {
    $fromVariant = iterator_to_array((new Omocodia())->variants(CodiceFiscale::from('RSSMRA95E05F20RU')));
    $fromCanonical = iterator_to_array((new Omocodia())->variants(CodiceFiscale::from('RSSMRA95E05F205Z')));

    $valuesOf = fn (array $codes) => array_map(fn ($c) => $c->value(), $codes);

    expect($valuesOf($fromVariant))->toEqualCanonicalizing($valuesOf($fromCanonical));
});
