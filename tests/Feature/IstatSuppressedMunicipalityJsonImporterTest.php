<?php

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\IstatSuppressedMunicipalityJsonImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

/*
 * The fixture holds rows of ISTAT's Situas report 128 "Elenco comuni
 * soppressi e non ricostituiti" as recorded on 2026-11-16
 * (pdata=17/03/1861), kept verbatim: the 506 rows for the 489 codes
 * 2.3.1 resolved that ANPR lacks (#124), plus H837 San Felice, a code
 * ANPR also carries.
 */
function istatReport128(): string
{
    return file_get_contents(__DIR__.'/../Fixtures/istat_comuni_soppressi_sample.json');
}

test('imports a suppressed comune with one era ending on the date the suppression took effect', function () {
    (new IstatSuppressedMunicipalityJsonImporter())->import(istatReport128());

    $eras = Municipality::where('code', 'A073')->get();
    expect($eras)->toHaveCount(1)
        ->and($eras[0]->name)->toBe('Agliate')
        ->and($eras[0]->province)->toBe('MI')
        ->and($eras[0]->istat_code)->toBe('015802')
        ->and($eras[0]->valid_from->toDateString())->toBe('1861-03-17')
        ->and($eras[0]->valid_to->toDateString())->toBe('1869-04-01')
        ->and($eras[0]->name_normalized)->toBe('AGLIATE');
});

test('a comune split between two successors is imported once', function () {
    $count = (new IstatSuppressedMunicipalityJsonImporter())->import(istatReport128());

    expect($count)->toBe(490)
        ->and(Municipality::count())->toBe(490)
        ->and(Municipality::where('code', 'A156')->count())->toBe(1);
});

test('a code the higher-priority source carries is skipped, keeping that source\'s eras', function () {
    Municipality::query()->toBase()->insert([
        'code' => 'H837', 'name' => 'SAN FELICE', 'name_normalized' => 'SAN FELICE', 'province' => 'BZ',
        'istat_code' => '021081', 'valid_from' => '1948-03-14', 'valid_to' => '1974-09-18',
    ]);

    (new IstatSuppressedMunicipalityJsonImporter())->import(istatReport128(), ['H837' => ['1948-03-14']]);

    $eras = Municipality::where('code', 'H837')->get();
    expect($eras)->toHaveCount(1)
        ->and($eras[0]->name)->toBe('SAN FELICE')
        ->and($eras[0]->valid_from->toDateString())->toBe('1948-03-14');
});

test('a row it wrote earlier is removed once the higher-priority source carries the code', function () {
    $importer = new IstatSuppressedMunicipalityJsonImporter();
    $importer->import(istatReport128());
    Municipality::query()->toBase()->insert([
        'code' => 'A073', 'name' => 'AGLIATE', 'name_normalized' => 'AGLIATE', 'province' => 'MI',
        'istat_code' => '015802', 'valid_from' => '1862-01-01', 'valid_to' => '1869-04-01',
    ]);

    $importer->import(istatReport128(), ['A073' => ['1862-01-01']]);

    $eras = Municipality::where('code', 'A073')->get();
    expect($eras)->toHaveCount(1)
        ->and($eras[0]->name)->toBe('AGLIATE')
        ->and($eras[0]->valid_from->toDateString())->toBe('1862-01-01');
});

test('re-running the import leaves the table unchanged', function () {
    $importer = new IstatSuppressedMunicipalityJsonImporter();
    $importer->import(istatReport128());
    $first = Municipality::orderBy('code')->get(['code', 'name', 'province', 'istat_code', 'valid_from', 'valid_to'])->toArray();

    $importer->import(istatReport128());

    expect(Municipality::orderBy('code')->get(['code', 'name', 'province', 'istat_code', 'valid_from', 'valid_to'])->toArray())->toBe($first);
});

test('the era starts of the report are one 1861-03-17 era per code', function () {
    $eraStarts = (new IstatSuppressedMunicipalityJsonImporter())->eraStarts(istatReport128());

    expect($eraStarts)->toHaveCount(490)
        ->and($eraStarts['A073'])->toBe(['1861-03-17']);
});

test('a row whose cadastral code is not a valid birthplace code is skipped', function () {
    $json = str_replace('"COD_CATASTO":"A073"', '"COD_CATASTO":"ND"', istatReport128());

    $count = (new IstatSuppressedMunicipalityJsonImporter())->import($json);

    expect($count)->toBe(489)
        ->and(Municipality::where('code', 'ND')->exists())->toBeFalse();
});

test('a payload without a resultset array is rejected', function () {
    (new IstatSuppressedMunicipalityJsonImporter())->import('{"error":"timeout"}');
})->throws(RuntimeException::class, 'resultset');
