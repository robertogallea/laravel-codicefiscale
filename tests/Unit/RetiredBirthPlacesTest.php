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
        // ...including a second code of a comune that still exists.
        ->and(retiredDomestic('M219')->province())->toBe('SS')
        ->and(retiredDomestic('M222')->province())->toBe('CN')
        // 2. The province the place lies in today.
        ->and(retiredDomestic('M215')->province())->toBe('TN')
        ->and(retiredDomestic('M224')->province())->toBe('CR')
        ->and(retiredDomestic('D687')->province())->toBe('PV')
        ->and(retiredDomestic('F554')->province())->toBe('PU')
        ->and(retiredDomestic('I366')->province())->toBe('IM')
        // 3. Now outside Italy: the Italian province that administered it.
        ->and(retiredDomestic('M293')->province())->toBe('FU')
        ->and(retiredDomestic('M296')->province())->toBe('PL')
        ->and(retiredDomestic('M310')->province())->toBe('ZA')
        ->and(retiredDomestic('H993')->province())->toBe('PL');
});

test('no code is on both the retired domestic and the retired foreign list', function () {
    $domestic = array_map(static fn (DomesticBirthPlace $place): string => $place->code()->value(), RetiredBirthPlaces::domestic());
    $foreign = array_map(static fn (ForeignBirthPlace $place): string => $place->code()->value(), RetiredBirthPlaces::foreign());

    expect(array_intersect($domestic, $foreign))->toBe([]);
});

/**
 * The source notes ADR-0012 requires every entry to record - data in
 * the list itself, not part of the public API.
 *
 * @return array<string, array{string, string, string, string}> code => [name, province, name note, province note]
 */
function retiredDomesticEntries(): array
{
    return (new ReflectionClassConstant(RetiredBirthPlaces::class, 'DOMESTIC'))->getValue();
}

test('every retired domestic entry records a source note for its name and for its province', function () {
    foreach (retiredDomesticEntries() as $code => $entry) {
        expect($entry)->toHaveCount(4, "$code has a name and a province note")
            ->and(trim($entry[2]))->not->toBe('', "$code has a name note")
            ->and(trim($entry[3]))->not->toBe('', "$code has a province note");
    }
});

test('every name is confirmed - by Allegato A or, for the two it lacks, the maintainer - and D178 records 2.3.1\'s wrong name', function () {
    $unconfirmedNames = array_keys(array_filter(
        retiredDomesticEntries(),
        static fn (array $entry): bool => str_starts_with($entry[2], 'unconfirmed'),
    ));

    expect($unconfirmedNames)->toBe([])
        ->and(retiredDomesticEntries()['M306'][2])->toStartWith('maintainer')
        ->and(retiredDomesticEntries()['M310'][2])->toStartWith('maintainer')
        ->and(retiredDomesticEntries()['D178'][2])->toContain('CROGNALETO');
});

test('every province is confirmed by a source: ANPR, a law, OpenStreetMap or the maintainer', function () {
    $unconfirmedProvinces = array_keys(array_filter(
        retiredDomesticEntries(),
        static fn (array $entry): bool => str_starts_with($entry[3], 'unconfirmed'),
    ));

    expect($unconfirmedProvinces)->toBe([])
        ->and(retiredDomesticEntries()['B930'][3])->toStartWith('ANPR')
        ->and(retiredDomesticEntries()['M205'][3])->toStartWith('Legge')
        ->and(retiredDomesticEntries()['M215'][3])->toStartWith('OSM')
        ->and(retiredDomesticEntries()['H993'][3])->toStartWith('maintainer');
});
