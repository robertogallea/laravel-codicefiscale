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
        ->and($usa->valid_to)->toBeNull()
        ->and($usa->name_normalized)->toBe("STATI UNITI D'AMERICA");
});

test('re-running the import backfills name_normalized on pre-existing rows', function () {
    $xlsx = file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx');
    $importer = new ForeignCountryXlsxImporter();

    $importer->import($xlsx);
    ForeignCountry::query()->update(['name_normalized' => null]);

    $importer->import($xlsx);

    expect(ForeignCountry::where('code', 'Z404')->first()->name_normalized)->toBe("STATI UNITI D'AMERICA");
});

test('re-running the import upserts existing rows instead of duplicating them', function () {
    $xlsx = file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx');
    $importer = new ForeignCountryXlsxImporter();

    $countFirstRun = $importer->import($xlsx);
    $importer->import($xlsx);

    expect(ForeignCountry::count())->toBe($countFirstRun);
});

test('re-running the import against genuinely updated data updates the existing row rather than duplicating it', function () {
    $importer = new ForeignCountryXlsxImporter();
    $importer->import(file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx'));
    $countFirstRun = ForeignCountry::count();

    // Real edits to the shared-string text a real update would carry -
    // not a fabricated file format, just a modified copy of the real one.
    $updatedXlsx = xlsxWithReplacedSharedString(
        __DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx',
        "STATI UNITI D'AMERICA",
        'STATI UNITI (RINOMINATO)',
    );
    $importer->import($updatedXlsx);

    expect(ForeignCountry::count())->toBe($countFirstRun)
        ->and(ForeignCountry::where('code', 'Z404')->first()->name)->toBe('STATI UNITI (RINOMINATO)');
});

function xlsxWithReplacedSharedString(string $originalPath, string $search, string $replace): string
{
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
    copy($originalPath, $tempFile);

    $zip = new ZipArchive();
    $zip->open($tempFile);
    $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
    $zip->addFromString('xl/sharedStrings.xml', str_replace($search, $replace, $sharedStrings));
    $zip->close();

    $contents = file_get_contents($tempFile);
    unlink($tempFile);

    return $contents;
}
