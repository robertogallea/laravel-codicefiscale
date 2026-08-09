<?php

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Xlsx\XlsxReader;

test('reads the header row and known real data rows from the real MAECI stati-esteri file', function () {
    $rows = (new XlsxReader())->read(file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx'));

    expect($rows[0])->toBe([
        'ID', 'DENOMINAZIONE', 'DENOMINAZIONEISTAT', 'DENOMINAZIONEISTAT_EN',
        'DATAINIZIOVALIDITA', 'DATAFINEVALIDITA', 'CODISO3166_1_ALPHA3', 'CODMAE',
        'CODMIN', 'CODAT', 'CODISTAT', 'CITTADINANZA', 'NASCITA', 'RESIDENZA',
        'FONTE', 'TIPO', 'CODISOSOVRANO', 'DATAULTIMOAGG',
    ]);

    $usa = collect($rows)->first(fn (array $row) => ($row[9] ?? null) === 'Z404');
    expect($usa)->not->toBeNull()
        ->and($usa[1])->toBe("STATI UNITI D'AMERICA")
        ->and($usa[6])->toBe('USA');
});

test('reads at least 238 real country rows (plus the header)', function () {
    $rows = (new XlsxReader())->read(file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx'));

    expect(count($rows))->toBeGreaterThanOrEqual(239);
});
