<?php

use Illuminate\Support\Facades\Http;
use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Data\RetiredBirthPlaces;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Parsing\Parser;
use Robertogallea\CodiceFiscale\Validation\Validator;

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
        ->and(ForeignCountry::count())->toBe(254);
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
        ->and(ForeignCountry::count())->toBe(254);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'archivio_comuni'));
});

test('re-running the command upserts existing rows instead of duplicating them', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(Municipality::count())->toBe(3)
        ->and(ForeignCountry::count())->toBe(254);
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

test('a retired foreign code no source carries resolves after the import, with its ADR-0013 country code', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $eras = app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('Z136'));
    expect($eras)->toHaveCount(1)
        ->and($eras[0])->toBeInstanceOf(ForeignBirthPlace::class)
        ->and($eras[0]->name())->toBe('GEORGIA')
        ->and($eras[0]->country()->value())->toBe('GEO');
});

test('every retired foreign code resolves after the import, as the list describes it', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);

    foreach (RetiredBirthPlaces::foreign() as $retired) {
        $eras = $repository->eras($retired->code());

        expect($eras)->toHaveCount(1, "{$retired->code()->value()} resolves")
            ->and($eras[0]->name())->toBe($retired->name())
            ->and($eras[0]->country()->value())->toBe($retired->country()->value());
    }
});

test('a codice fiscale carrying a retired foreign code validates and parses to that birthplace', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);
    $result = (new Validator($repository))->validate('RSSMRA70A01Z136P');
    $birthPlace = (new Parser($repository))->parse(CodiceFiscale::from('RSSMRA70A01Z136P'))->birthPlace();

    expect($result->errors())->toBe([])
        ->and($birthPlace)->toBeInstanceOf(ForeignBirthPlace::class)
        ->and($birthPlace->name())->toBe('GEORGIA')
        ->and($birthPlace->country()->value())->toBe('GEO');
});

test('a retired foreign code is found by name search', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $codes = array_map(
        static fn ($place): string => $place->code()->value(),
        app(BirthPlaceRepository::class)->search('Vietnam del Sud'),
    );

    expect($codes)->toContain('Z244');
});

test('when the downloaded source carries a code that is also on the retired list, the source row wins', function () {
    // The real MAECI fixture, with the USA row's CODAT changed to Z136:
    // the source now "carries" Z136, under a name the list never uses.
    Http::fake([
        'anagrafenazionale.interno.it/*tabella_2_statiesteri.xlsx' => Http::response(xlsxWithReplacedSharedString(
            __DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx',
            '<t>Z404</t>',
            '<t>Z136</t>',
        )),
    ]);

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    $eras = app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('Z136'));
    expect($eras)->toHaveCount(1)
        ->and($eras[0]->name())->toBe("STATI UNITI D'AMERICA")
        ->and($eras[0]->country()->value())->toBe('USA');
});

test('re-running the command keeps exactly one era per retired foreign code', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('Z800')))->toHaveCount(1);
});
