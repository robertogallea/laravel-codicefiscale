<?php

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\ForeignCountryXlsxImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;

test('imports the real MAECI fixture, mapping CODAT to code and CODISO3166_1_ALPHA3 to country_code', function () {
    $xlsx = file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx');

    $count = (new ForeignCountryXlsxImporter())->import($xlsx);

    // 238 total rows in the source, but 29 have no CODAT at all (e.g.
    // dependent territories without their own code, "APOLIDE"
    // stateless, "ITALIA" itself since it isn't foreign) - those
    // can't map to a BirthPlaceCode, so they're correctly skipped.
    expect($count)->toBe(209);

    $usa = ForeignCountry::where('code', 'Z404')->first();
    expect($usa)->not->toBeNull()
        ->and($usa->name)->toBe("STATI UNITI D'AMERICA")
        ->and($usa->country_code)->toBe('USA')
        ->and($usa->valid_from->toDateString())->toBe('1900-01-01')
        ->and($usa->valid_to)->toBeNull();
});

test('re-running the import upserts existing rows instead of duplicating them', function () {
    $xlsx = file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx');
    $importer = new ForeignCountryXlsxImporter();

    $countFirstRun = $importer->import($xlsx);
    $importer->import($xlsx);

    expect(ForeignCountry::count())->toBe($countFirstRun);
});
