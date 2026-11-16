<?php

use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
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

function retiredDomestic(string $code): DomesticBirthPlace
{
    foreach (RetiredBirthPlaces::domestic() as $place) {
        if ($place->code()->value() === $code) {
            return $place;
        }
    }

    throw new RuntimeException("$code is not in the retired domestic list.");
}

test('the retired domestic list holds the 70 codes 2.3.1 resolved that no source carries, each once', function () {
    $codes = array_map(static fn (DomesticBirthPlace $place): string => $place->code()->value(), RetiredBirthPlaces::domestic());

    expect($codes)->toHaveCount(70)
        ->and(array_unique($codes))->toHaveCount(70)
        ->and($codes)->toContain('M215', 'M252', 'D178', 'M310', 'B930', 'I055');
});

test('every retired domestic code has one maximally wide era, a province and no ISTAT code', function () {
    foreach (RetiredBirthPlaces::domestic() as $place) {
        expect($place->validFrom()->format('Y-m-d'))->toBe('1861-03-17')
            ->and($place->validTo())->toBeNull()
            ->and($place->province())->toMatch('/^[A-Z]{2}$/')
            ->and($place->istatCode())->toBe('')
            ->and($place->name())->not->toBe('');
    }
});

test('D178 is Crodo Mozzio, not the Crognaleto 2.3.1 named it', function () {
    expect(retiredDomestic('D178')->name())->toBe('CRODO MOZZIO')
        ->and(retiredDomestic('D178')->province())->toBe('VB');
});

test('a retired domestic code takes the province a source records, else today\'s, else the historical Italian one', function () {
    // 1. ANPR records the place itself under another code.
    expect(retiredDomestic('B930')->province())->toBe('GO')
        ->and(retiredDomestic('G380')->province())->toBe('PL')
        ->and(retiredDomestic('M306')->province())->toBe('BZ')
        // 2. The province the place lies in today.
        ->and(retiredDomestic('M215')->province())->toBe('TN')
        ->and(retiredDomestic('M224')->province())->toBe('CR')
        ->and(retiredDomestic('I366')->province())->toBe('IM')
        // 3. Now outside Italy: the Italian province that administered it.
        ->and(retiredDomestic('M293')->province())->toBe('FU')
        ->and(retiredDomestic('M296')->province())->toBe('PL')
        ->and(retiredDomestic('M310')->province())->toBe('ZA');
});

test('no code is on both the retired domestic and the retired foreign list', function () {
    $domestic = array_map(static fn (DomesticBirthPlace $place): string => $place->code()->value(), RetiredBirthPlaces::domestic());
    $foreign = array_map(static fn (ForeignBirthPlace $place): string => $place->code()->value(), RetiredBirthPlaces::foreign());

    expect(array_intersect($domestic, $foreign))->toBe([]);
});
