<?php

use Illuminate\Support\Facades\Http;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

function fakeUpdatePlacesHttp(): void
{
    Http::fake([
        'anagrafenazionale.interno.it/*ANPR_archivio_comuni.csv' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv')
        ),
        'anagrafenazionale.interno.it/*tabella_2_statiesteri.xlsx' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx')
        ),
    ]);
}

test('running the command against recorded fixtures populates both tables with the correct row counts', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(Municipality::count())->toBe(3)
        ->and(ForeignCountry::count())->toBe(209);
});

test('a known tricky row is asserted exactly: the province-changing municipality produces two era-records', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $eras = Municipality::where('code', 'A004')->orderBy('valid_from')->get();
    expect($eras)->toHaveCount(2)
        ->and($eras[0]->province)->toBe('MI')
        ->and($eras[1]->province)->toBe('LO');
});

test('a known tricky row is asserted exactly: a foreign country CODAT maps to the right BirthPlaceCode', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $usa = ForeignCountry::where('code', 'Z404')->first();
    expect($usa->country_code)->toBe('USA');
});

test('--municipalities-only updates only the municipalities dataset', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect(Municipality::count())->toBe(3)
        ->and(ForeignCountry::count())->toBe(0);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'statiesteri'));
});

test('--countries-only updates only the countries dataset', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    expect(Municipality::count())->toBe(0)
        ->and(ForeignCountry::count())->toBe(209);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'archivio_comuni'));
});

test('re-running the command upserts existing rows instead of duplicating them', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(Municipality::count())->toBe(3)
        ->and(ForeignCountry::count())->toBe(209);
});

test('re-running the command against genuinely updated fixtures updates the existing row rather than duplicating it', function () {
    $originalCsv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');
    $updatedCsv = str_replace('"ROMA","ROMA"', '"ROMA CAPITALE","ROMA CAPITALE"', $originalCsv);

    Http::fake([
        'anagrafenazionale.interno.it/*ANPR_archivio_comuni.csv' => Http::sequence()
            ->push($originalCsv)
            ->push($updatedCsv),
    ]);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect(Municipality::count())->toBe(3)
        ->and(Municipality::where('code', 'H501')->first()->name)->toBe('ROMA CAPITALE');
});
