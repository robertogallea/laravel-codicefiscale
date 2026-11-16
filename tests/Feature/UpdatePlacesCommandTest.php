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

/*
 * Foreign rows after a full import: MAECI's 209, the 45 retired codes
 * no source carries, and the 31 codes only ISTAT has - #123's 24
 * territories, #122's six ceased states and Z221 Hong Kong.
 */
const FOREIGN_ROWS_AFTER_IMPORT = 285;

function fakeUpdatePlacesHttp(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'anagrafenazionale.interno.it/*ANPR_archivio_comuni.csv' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv')
        ),
        'anagrafenazionale.interno.it/*tabella_2_statiesteri.xlsx' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx')
        ),
        'situas-servizi.istat.it/*' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/istat_storico_paesi_esteri_sample.json')
        ),
    ]);
}

test('running the command against recorded fixtures populates both tables with the correct row counts', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(Municipality::count())->toBe(3)
        ->and(ForeignCountry::count())->toBe(FOREIGN_ROWS_AFTER_IMPORT);
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
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'situas-servizi.istat.it'));
});

test('--countries-only updates only the countries dataset', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    expect(Municipality::count())->toBe(0)
        ->and(ForeignCountry::count())->toBe(FOREIGN_ROWS_AFTER_IMPORT);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'archivio_comuni'));
    Http::assertSent(fn ($request) => str_contains($request->url(), 'situas-servizi.istat.it'));
});

test('re-running the command upserts existing rows instead of duplicating them', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(Municipality::count())->toBe(3)
        ->and(ForeignCountry::count())->toBe(FOREIGN_ROWS_AFTER_IMPORT);
});

test('re-running the command against genuinely updated fixtures updates the existing row rather than duplicating it', function () {
    $originalCsv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');
    $updatedCsv = str_replace('"ROMA","ROMA"', '"ROMA CAPITALE","ROMA CAPITALE"', $originalCsv);

    Http::preventStrayRequests();
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
    Http::preventStrayRequests();
    Http::fake([
        'anagrafenazionale.interno.it/*tabella_2_statiesteri.xlsx' => Http::response(xlsxWithReplacedSharedString(
            __DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx',
            '<t>Z404</t>',
            '<t>Z136</t>',
        )),
        'situas-servizi.istat.it/*' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/istat_storico_paesi_esteri_sample.json')
        ),
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

test('a territory MAECI has no CODAT for resolves from ISTAT, and a codice fiscale carrying it validates and parses', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);
    $result = (new Validator($repository))->validate('RSSMRA70A01Z113F');
    $birthPlace = (new Parser($repository))->parse(CodiceFiscale::from('RSSMRA70A01Z113F'))->birthPlace();

    expect($result->errors())->toBe([])
        ->and($birthPlace)->toBeInstanceOf(ForeignBirthPlace::class)
        ->and($birthPlace->name())->toBe('Gibilterra')
        ->and($birthPlace->country()->value())->toBe('GIB');
    Http::assertSent(fn ($request) => str_contains($request->url(), 'situas-servizi.istat.it')
        && str_contains(urldecode($request->url()), 'pfun=469')
        && str_contains(urldecode($request->url()), 'pdata=01/01/1990'));
});

test('a code both sources carry keeps MAECI\'s name and country code', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $eras = app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('Z404'));
    expect($eras)->toHaveCount(1)
        ->and($eras[0]->name())->toBe("STATI UNITI D'AMERICA")
        ->and($eras[0]->country()->value())->toBe('USA');
});

test('Z501, which MAECI reassigned to Aruba, keeps its historical meaning from ISTAT: Antille olandesi', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    $eras = app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('Z501'));
    expect($eras)->toHaveCount(1)
        ->and($eras[0]->name())->toBe('Antille olandesi')
        ->and($eras[0]->country()->value())->toBe('ANT');
});

test('when ISTAT carries a code that is also on the retired list, ISTAT\'s row wins', function () {
    // The real ISTAT fixture, with Gibilterra's COD_AT changed to Z136:
    // ISTAT now "carries" Z136, under a name the list never uses.
    Http::preventStrayRequests();
    Http::fake([
        'anagrafenazionale.interno.it/*tabella_2_statiesteri.xlsx' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx')
        ),
        'situas-servizi.istat.it/*' => Http::response(str_replace(
            '"COD_AT":"Z113"',
            '"COD_AT":"Z136"',
            file_get_contents(__DIR__.'/../Fixtures/istat_storico_paesi_esteri_sample.json'),
        )),
    ]);

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    $eras = app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('Z136'));
    expect($eras)->toHaveCount(1)
        ->and($eras[0]->name())->toBe('Gibilterra')
        ->and($eras[0]->country()->value())->toBe('GIB');
});

test('a config published before the ISTAT source existed still imports it, from the package default URL', function () {
    fakeUpdatePlacesHttp();
    // A published codicefiscale.php replaces the package's whole
    // `sources` array (mergeConfigFrom merges top-level keys only).
    config(['codicefiscale.sources' => [
        'municipalities' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/ANPR_archivio_comuni.csv',
        'countries' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/tabella_2_statiesteri.xlsx',
    ]]);

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    expect(app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('Z113')))->toHaveCount(1);
    Http::assertSent(fn ($request) => urldecode($request->url()) === 'https://situas-servizi.istat.it/publish/reportspooljson?pfun=469&pdata=01/01/1990');
});

test('the ISTAT source URL is configurable', function () {
    fakeUpdatePlacesHttp();
    config(['codicefiscale.sources.historical_countries' => 'https://situas-servizi.istat.it/publish/reportspooljson?pfun=469&pdata=01/01/1980']);

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    Http::assertSent(fn ($request) => str_contains(urldecode($request->url()), 'pdata=01/01/1980'));
});

test('after importing the recorded sources, every foreign code 2.3.1 resolved resolves', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);
    $foreign = array_values(array_filter(
        v2ParityCodes('codes.txt'),
        static fn (string $code): bool => str_starts_with($code, 'Z'),
    ));
    $unresolved = array_values(array_filter(
        $foreign,
        static fn (string $code): bool => $repository->eras(BirthPlaceCode::from($code)) === [],
    ));

    expect($foreign)->toHaveCount(283)
        ->and($unresolved)->toBe([]);
});
