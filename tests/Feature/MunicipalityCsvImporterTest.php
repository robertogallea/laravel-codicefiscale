<?php

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\MunicipalityCsvImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

test('imports the real Abbadia Cerreto and Roma fixture rows correctly', function () {
    $csv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');

    $count = (new MunicipalityCsvImporter())->import($csv);

    expect($count)->toBe(3);

    $milanoEra = Municipality::where('code', 'A004')->where('province', 'MI')->first();
    expect($milanoEra)->not->toBeNull()
        ->and($milanoEra->name)->toBe('ABBADIA CERRETO')
        ->and($milanoEra->istat_code)->toBe('015001')
        ->and($milanoEra->valid_from->toDateString())->toBe('1861-03-17')
        // Source DATACESSAZIONE is inclusive (1992-04-15, last valid day);
        // our domain's validTo is exclusive, so it's +1 day.
        ->and($milanoEra->valid_to->toDateString())->toBe('1992-04-16');

    $lodiEra = Municipality::where('code', 'A004')->where('province', 'LO')->first();
    expect($lodiEra)->not->toBeNull()
        ->and($lodiEra->valid_from->toDateString())->toBe('1992-04-16')
        // Source DATACESSAZIONE "9999-12-31" (still active) -> open-ended.
        ->and($lodiEra->valid_to)->toBeNull();

    $roma = Municipality::where('code', 'H501')->first();
    expect($roma->name)->toBe('ROMA')
        ->and($roma->valid_to)->toBeNull()
        ->and($roma->name_normalized)->toBe('ROMA');
});

test('imports name_normalized alongside name, for search matching', function () {
    $csv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');

    (new MunicipalityCsvImporter())->import($csv);

    expect(Municipality::where('code', 'A004')->where('province', 'MI')->first()->name_normalized)
        ->toBe('ABBADIA CERRETO');
});

test('re-running the import backfills name_normalized on pre-existing rows', function () {
    $csv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');
    $importer = new MunicipalityCsvImporter();

    // Simulate an installation that populated municipalities before
    // name_normalized existed: the column starts out null.
    $importer->import($csv);
    Municipality::query()->update(['name_normalized' => null]);

    $importer->import($csv);

    expect(Municipality::where('code', 'H501')->first()->name_normalized)->toBe('ROMA');
});

test('re-running the import upserts existing rows instead of duplicating them', function () {
    $csv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');
    $importer = new MunicipalityCsvImporter();

    $importer->import($csv);
    $importer->import($csv);

    expect(Municipality::count())->toBe(3);
});

test('re-running the import with updated data updates the existing row rather than inserting a new one', function () {
    $importer = new MunicipalityCsvImporter();
    $importer->import(file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv'));

    $updatedCsv = str_replace('"ROMA","ROMA"', '"ROMA CAPITALE","ROMA CAPITALE"', file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv'));
    $importer->import($updatedCsv);

    expect(Municipality::count())->toBe(3)
        ->and(Municipality::where('code', 'H501')->first()->name)->toBe('ROMA CAPITALE');
});
