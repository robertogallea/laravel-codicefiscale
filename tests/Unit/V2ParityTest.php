<?php

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Data\RetiredBirthPlaces;

/*
 * v2 parity: 3.x must resolve every birthplace code 2.3.1's default
 * decoder resolved (InternationalCitiesStaticList::getList(), which
 * merges ItalianCitiesStaticList). Every such code is accounted for in
 * exactly one place - the retired list shipped with the package, the
 * codes the downloaded sources are expected to carry, or a known gap
 * owned by an open issue. This offline layer runs on every PR; the
 * network layer (V2ParityNetworkTest) checks the sources really do
 * carry what they are expected to.
 */

test('the 2.3.1 fixture pins every code its default decoder resolved', function () {
    $codes = v2ParityCodes('codes.txt');

    expect($codes)->toHaveCount(10670)
        ->and(array_unique($codes))->toHaveCount(10670)
        ->and(array_filter($codes, static fn (string $code): bool => preg_match('/^[A-Z]\d{3}$/', $code) !== 1))->toBe([]);
});

test('every 2.3.1 code is either shipped as retired, expected from a source, or a known gap - exactly one of them', function () {
    $retired = array_map(
        static fn (BirthPlace $place): string => $place->code()->value(),
        [...RetiredBirthPlaces::domestic(), ...RetiredBirthPlaces::foreign()],
    );
    $expected = v2ParityCodes('expected-from-sources.txt');
    $gaps = array_keys(v2ParityKnownGaps());

    $accountedFor = [...$retired, ...$expected, ...$gaps];
    sort($accountedFor);

    expect($accountedFor)->toBe(v2ParityCodes('codes.txt'));
});

test('no 2.3.1 code is a known gap any more: #122, #123 and #124 closed them all', function () {
    expect(v2ParityKnownGaps())->toBe([]);
});
