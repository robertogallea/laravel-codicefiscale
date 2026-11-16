<?php

use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Data\RetiredBirthPlaces;

function retiredForeign(string $code): ForeignBirthPlace
{
    foreach (RetiredBirthPlaces::foreign() as $place) {
        if ($place->code()->value() === $code) {
            return $place;
        }
    }

    throw new RuntimeException("$code is not in the retired foreign list.");
}

test('a retired code of a country that still exists carries that country\'s current ISO code', function () {
    $georgia = retiredForeign('Z136');

    expect($georgia->name())->toBe('GEORGIA')
        ->and($georgia->country()->value())->toBe('GEO');
});

test('a ceased entity with its own former ISO code carries that code', function () {
    expect(retiredForeign('Z245')->country()->value())->toBe('VDR')
        ->and(retiredForeign('Z517')->country()->value())->toBe('PCZ')
        ->and(retiredForeign('Z346')->country()->value())->toBe('AFI');
});

test('a territory with no ISO code of its own carries the code of the state it belongs to today', function () {
    expect(retiredForeign('Z800')->name())->toBe('DIPENDENZE CANADESI')
        ->and(retiredForeign('Z800')->country()->value())->toBe('CAN')
        ->and(retiredForeign('Z238')->country()->value())->toBe('JPN')
        ->and(retiredForeign('Z201')->country()->value())->toBe('YEM');
});

test('every retired foreign code has one maximally wide era, so it is always attributable', function () {
    $places = RetiredBirthPlaces::foreign();
    $codes = array_map(static fn (ForeignBirthPlace $place): string => $place->code()->value(), $places);

    expect($places)->toHaveCount(45)
        ->and(array_unique($codes))->toHaveCount(45);

    foreach ($places as $place) {
        expect($place->validFrom()->format('Y-m-d'))->toBe('1900-01-01')
            ->and($place->validTo())->toBeNull();
    }
});
