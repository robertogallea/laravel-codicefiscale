<?php

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\IstatForeignCountryJsonImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;

/*
 * The fixture is ISTAT's Situas report 469 "Storico Paesi esteri" as
 * recorded on 2026-11-16 (pdata=01/01/1990): 262 rows, 239 with a
 * COD_AT - 232 current (Stato, Territorio, Territorio/Altro) and 7
 * historical (Stato storico, Territorio storico).
 */
function istatReport469(): string
{
    return file_get_contents(__DIR__.'/../Fixtures/istat_storico_paesi_esteri_sample.json');
}

test('imports a current territory MAECI has no CODAT for, with a maximally wide era', function () {
    (new IstatForeignCountryJsonImporter())->importCurrent(istatReport469());

    $gibraltar = ForeignCountry::where('code', 'Z113')->first();
    expect($gibraltar)->not->toBeNull()
        ->and($gibraltar->name)->toBe('Gibilterra')
        ->and($gibraltar->country_code)->toBe('GIB')
        ->and($gibraltar->valid_from->toDateString())->toBe('1900-01-01')
        ->and($gibraltar->valid_to)->toBeNull()
        ->and($gibraltar->name_normalized)->toBe('GIBILTERRA');
});

test('importing the current rows leaves out every historical row', function () {
    $count = (new IstatForeignCountryJsonImporter())->importCurrent(istatReport469());

    expect($count)->toBe(232)
        ->and(ForeignCountry::where('code', 'Z135')->exists())->toBeFalse()
        ->and(ForeignCountry::where('code', 'Z501')->exists())->toBeFalse();
});

test('importing the historical rows imports only the ceased states and territories', function () {
    $count = (new IstatForeignCountryJsonImporter())->importHistorical(istatReport469());

    expect($count)->toBe(7)
        ->and(ForeignCountry::orderBy('code')->pluck('country_code', 'code')->all())->toBe([
            'Z105' => 'CSK',
            'Z111' => 'DDR',
            'Z118' => 'YUG',
            'Z135' => 'SUN',
            'Z157' => 'SCG',
            'Z250' => 'YMD',
            'Z501' => 'ANT',
        ])
        ->and(ForeignCountry::where('code', 'Z135')->value('name'))->toBe('Unione Sovietica');
});

test('rows with a malformed COD_AT or no ISO alpha-3 code are skipped, not imported', function () {
    $json = str_replace(
        ['"COD_AT":"Z113"', '"ISO_3166_ALPHA3":"GRL"'],
        ['"COD_AT":"ND"', '"ISO_3166_ALPHA3":""'],
        istatReport469(),
    );

    $count = (new IstatForeignCountryJsonImporter())->importCurrent($json);

    expect($count)->toBe(230)
        ->and(ForeignCountry::where('code', 'Z113')->exists())->toBeFalse()
        ->and(ForeignCountry::where('code', 'ND')->exists())->toBeFalse()
        ->and(ForeignCountry::where('code', 'Z402')->exists())->toBeFalse();
});
