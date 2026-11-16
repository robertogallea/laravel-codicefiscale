<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Data\RetiredBirthPlaces;
use Robertogallea\CodiceFiscale\Enums\ValidationError;
use Robertogallea\CodiceFiscale\Generation\Checksum;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\ForeignCountryXlsxImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Parsing\Parser;
use Robertogallea\CodiceFiscale\Support\BirthPlaceAttributability;
use Robertogallea\CodiceFiscale\Validation\Validator;

/*
 * Foreign rows after a full import: MAECI's 209, the 45 retired codes
 * no source carries, and the 31 codes only ISTAT has - #123's 24
 * territories, #122's six ceased states and Z221 Hong Kong.
 */
const FOREIGN_ROWS_AFTER_IMPORT = 285;

/*
 * Municipality rows after a full import: ANPR's 3, the 490 codes of the
 * report-128 fixture ANPR's sample lacks, and the 70 retired domestic
 * codes no source carries.
 */
const MUNICIPALITY_ROWS_AFTER_IMPORT = 563;

const ISTAT_REPORT_128_URL = 'situas.istat.it/ShibO2Module/api/Report/Spool/*';

const ISTAT_REPORT_469_URL = 'situas-servizi.istat.it/*pfun=469*';

function istatReport128Fixture(): string
{
    return file_get_contents(__DIR__.'/../Fixtures/istat_comuni_soppressi_sample.json');
}

/**
 * Serves a report in the {"resultset": [...]} shape page by page, the
 * way the Situas paged endpoint does, after $failFirst dropped
 * connections.
 */
function istatReport128Pages(string $resultsetJson, int $failFirst = 0): Closure
{
    $rows = json_decode($resultsetJson, true)['resultset'];
    $calls = 0;

    return static function (Request $request) use ($rows, $failFirst, &$calls) {
        if (++$calls <= $failFirst) {
            return Http::failedConnection()($request);
        }

        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $pageSize = (int) $query['PageSize'];
        $body = array_slice($rows, ((int) $query['PageNum'] - 1) * $pageSize, $pageSize);

        return Http::response(json_encode(['label' => [], 'totalRows' => count($rows), 'body' => $body]));
    };
}

/**
 * @param  array<string, mixed>  $overrides  URL pattern => fake response, replacing the recorded fixture for that source
 */
function fakeUpdatePlacesHttp(array $overrides = []): void
{
    Http::preventStrayRequests();
    Http::fake(array_replace([
        'anagrafenazionale.interno.it/*ANPR_archivio_comuni.csv' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv')
        ),
        'anagrafenazionale.interno.it/*tabella_2_statiesteri.xlsx' => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx')
        ),
        ISTAT_REPORT_128_URL => istatReport128Pages(istatReport128Fixture()),
        ISTAT_REPORT_469_URL => Http::response(
            file_get_contents(__DIR__.'/../Fixtures/istat_storico_paesi_esteri_sample.json')
        ),
    ], $overrides));
}

test('running the command against recorded fixtures populates both tables with the correct row counts', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(Municipality::count())->toBe(MUNICIPALITY_ROWS_AFTER_IMPORT)
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

    expect(Municipality::count())->toBe(MUNICIPALITY_ROWS_AFTER_IMPORT)
        ->and(ForeignCountry::count())->toBe(0);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'statiesteri'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'pfun=469'));
});

test('--countries-only updates only the countries dataset', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    expect(Municipality::count())->toBe(0)
        ->and(ForeignCountry::count())->toBe(FOREIGN_ROWS_AFTER_IMPORT);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'archivio_comuni'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'Report/Spool'));
    Http::assertSent(fn ($request) => str_contains($request->url(), 'pfun=469'));
});

test('re-running the command upserts existing rows instead of duplicating them', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places')->assertExitCode(0);

    expect(Municipality::count())->toBe(MUNICIPALITY_ROWS_AFTER_IMPORT)
        ->and(ForeignCountry::count())->toBe(FOREIGN_ROWS_AFTER_IMPORT);
});

test('re-running the command against genuinely updated fixtures updates the existing row rather than duplicating it', function () {
    $originalCsv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');
    $updatedCsv = str_replace('"ROMA","ROMA"', '"ROMA CAPITALE","ROMA CAPITALE"', $originalCsv);

    fakeUpdatePlacesHttp([
        'anagrafenazionale.interno.it/*ANPR_archivio_comuni.csv' => Http::sequence()
            ->push($originalCsv)
            ->push($updatedCsv),
    ]);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect(Municipality::count())->toBe(MUNICIPALITY_ROWS_AFTER_IMPORT)
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

/**
 * @return array<string, array{string, string}> code => [name, ISO alpha-3]
 */
function foreignCountriesSnapshot(): array
{
    return ForeignCountry::orderBy('code')->get()
        ->mapWithKeys(static fn (ForeignCountry $row): array => [$row->code => [$row->name, $row->country_code]])
        ->all();
}

test('every code ISTAT adds - #123\'s territories and #122\'s ceased states - validates and parses to a birthplace', function (string $code) {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    $first15 = 'RSSMRA70A01'.$code;
    $cf = $first15.(new Checksum())->calculate($first15);
    $repository = app(BirthPlaceRepository::class);

    expect((new Validator($repository))->validate($cf)->errors())->toBe([])
        ->and((new Parser($repository))->parse(CodiceFiscale::from($cf))->birthPlace())->toBeInstanceOf(ForeignBirthPlace::class);
})->with([
    // The 24 territories in #123's table.
    'Z113', 'Z122', 'Z212', 'Z231', 'Z324', 'Z339', 'Z402', 'Z508', 'Z518', 'Z520', 'Z529', 'Z531',
    'Z607', 'Z609', 'Z702', 'Z706', 'Z710', 'Z714', 'Z715', 'Z716', 'Z722', 'Z723', 'Z725', 'Z727',
    // #122's group A.
    'Z105', 'Z111', 'Z118', 'Z135', 'Z157', 'Z250',
]);

test('every code MAECI supplies keeps MAECI\'s name and country code, except Z501', function () {
    (new ForeignCountryXlsxImporter())->import(file_get_contents(__DIR__.'/../Fixtures/tabella_2_statiesteri_sample.xlsx'));
    $maeciOnly = foreignCountriesSnapshot();
    ForeignCountry::query()->delete();

    fakeUpdatePlacesHttp();
    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);
    $afterImport = array_intersect_key(foreignCountriesSnapshot(), $maeciOnly);

    expect($maeciOnly)->toHaveCount(209)
        ->and($maeciOnly['Z501'])->toBe(['ARUBA', 'ABW'])
        ->and($afterImport)->toHaveCount(209)
        ->and(array_diff_key($afterImport, ['Z501' => true]))->toBe(array_diff_key($maeciOnly, ['Z501' => true]));
});

test('re-running the command leaves the foreign_countries contents unchanged', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);
    $firstRun = foreignCountriesSnapshot();
    $this->artisan('codice-fiscale:update-places', ['--countries-only' => true])->assertExitCode(0);

    expect($firstRun)->toHaveCount(FOREIGN_ROWS_AFTER_IMPORT)
        ->and(foreignCountriesSnapshot())->toBe($firstRun);
});

/** @return list<string> valid_from|valid_to|name|province|istat_code per era, in order */
function municipalityEras(string $code): array
{
    return Municipality::where('code', $code)->orderBy('valid_from')->get()
        ->map(static fn (Municipality $row): string => implode('|', [
            $row->valid_from->toDateString(), $row->valid_to?->toDateString(), $row->name, $row->province, $row->istat_code,
        ]))
        ->all();
}

test('a comune suppressed before 1900 that ANPR lacks is known after the import, but not attributable to a codice fiscale', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);
    $eras = $repository->eras(BirthPlaceCode::from('A073'));

    expect($eras)->toHaveCount(1)
        ->and($eras[0])->toBeInstanceOf(DomesticBirthPlace::class)
        ->and($eras[0]->name())->toBe('Agliate')
        ->and($eras[0]->province())->toBe('MI')
        ->and($eras[0]->istatCode())->toBe('015802')
        ->and($eras[0]->validTo()?->format('Y-m-d'))->toBe('1869-04-01')
        ->and(BirthPlaceAttributability::era($eras, new DateTimeImmutable('1868-05-01'))?->name())->toBe('Agliate')
        ->and((new Validator($repository))->validate('RSSMRA80A01A073P')->errors())->toBe([ValidationError::BirthPlaceNotValidOnDate])
        ->and((new Parser($repository))->parse(CodiceFiscale::from('RSSMRA80A01A073P'))->birthPlace())->toBeNull();
    Http::assertSent(fn ($request) => $request->url() === 'https://situas.istat.it/ShibO2Module/api/Report/Spool/1861-03-17/128?PageNum=1&PageSize=200');
});

test('every retired domestic code resolves after the import, as the list describes it', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);

    foreach (RetiredBirthPlaces::domestic() as $retired) {
        $eras = $repository->eras($retired->code());

        expect($eras)->toHaveCount(1, "{$retired->code()->value()} resolves")
            ->and($eras[0]->name())->toBe($retired->name())
            ->and($eras[0]->province())->toBe($retired->province())
            ->and($eras[0]->istatCode())->toBe('')
            ->and($eras[0]->validTo())->toBeNull();
    }
});

test('a codice fiscale carrying a retired domestic code validates and parses to that birthplace', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    $repository = app(BirthPlaceRepository::class);
    $birthPlace = (new Parser($repository))->parse(CodiceFiscale::from('RSSMRA80A01M219N'))->birthPlace();

    expect((new Validator($repository))->validate('RSSMRA80A01M219N')->errors())->toBe([])
        ->and($birthPlace)->toBeInstanceOf(DomesticBirthPlace::class)
        ->and($birthPlace->name())->toBe('BONO')
        ->and($birthPlace->province())->toBe('TN');
});

test('a code both ANPR and ISTAT\'s suppressed-comuni report carry keeps exactly ANPR\'s eras', function () {
    // The real report-128 fixture, with H837's row moved onto A004.
    fakeUpdatePlacesHttp([ISTAT_REPORT_128_URL => istatReport128Pages(str_replace('"COD_CATASTO":"H837"', '"COD_CATASTO":"A004"', istatReport128Fixture()))]);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect(municipalityEras('A004'))->toBe([
        '1861-03-17|1992-04-16|ABBADIA CERRETO|MI|015001',
        '1992-04-16||ABBADIA CERRETO|LO|098001',
    ]);
});

test('once ANPR carries a retired domestic code, the bundled row is gone after the next run', function () {
    $originalCsv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');
    // The real ANPR fixture, with Roma's row moved onto M219.
    $csvCarryingM219 = str_replace('"H501","ROMA"', '"M219","ROMA"', $originalCsv);
    fakeUpdatePlacesHttp([
        'anagrafenazionale.interno.it/*ANPR_archivio_comuni.csv' => Http::sequence()->push($originalCsv)->push($csvCarryingM219),
    ]);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);
    $bundled = municipalityEras('M219');
    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect($bundled)->toBe(['1861-03-17||BONO|TN|'])
        ->and(municipalityEras('M219'))->toBe(['1992-04-04||ROMA|RM|058091']);
});

test('once ISTAT\'s suppressed-comuni report carries a retired domestic code, its row replaces the bundled one', function () {
    // The real report-128 fixture, with Agliate's row moved onto M219.
    fakeUpdatePlacesHttp([ISTAT_REPORT_128_URL => istatReport128Pages(str_replace('"COD_CATASTO":"A073"', '"COD_CATASTO":"M219"', istatReport128Fixture()))]);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);
    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect(municipalityEras('M219'))->toBe(['1861-03-17|1869-04-01|Agliate|MI|015802']);
});

test('a dropped connection to ISTAT\'s suppressed-comuni report is retried', function () {
    Sleep::fake();
    fakeUpdatePlacesHttp([
        ISTAT_REPORT_128_URL => istatReport128Pages(istatReport128Fixture(), failFirst: 2),
    ]);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    // ANPR once, then report 128: two dropped connections and its three pages.
    expect(app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('A073')))->toHaveCount(1);
    Http::assertSentCount(6);
});

test('when every attempt to download a domestic source fails, the command fails and the existing rows are untouched', function () {
    Sleep::fake();
    $calls = 0;
    $pages = istatReport128Pages(istatReport128Fixture());
    $csv = file_get_contents(__DIR__.'/../Fixtures/anpr_comuni_sample.csv');
    fakeUpdatePlacesHttp([
        // The second run would rename Roma, if it got that far.
        'anagrafenazionale.interno.it/*ANPR_archivio_comuni.csv' => Http::sequence()
            ->push($csv)
            ->push(str_replace('"ROMA","ROMA"', '"ROMA CAPITALE","ROMA CAPITALE"', $csv)),
        // The first run's three pages arrive; every later request drops.
        ISTAT_REPORT_128_URL => static function (Request $request) use ($pages, &$calls) {
            return ++$calls <= 3 ? $pages($request) : Http::failedConnection()($request);
        },
    ]);
    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);
    $before = Municipality::orderBy('id')->get()->toArray();

    expect(fn () => $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->run())
        ->toThrow(ConnectionException::class)
        ->and(Municipality::orderBy('id')->get()->toArray())->toBe($before);
});

test('a config published before the suppressed-comuni source existed still imports it, from the package default URL', function () {
    fakeUpdatePlacesHttp();
    config(['codicefiscale.sources' => [
        'municipalities' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/ANPR_archivio_comuni.csv',
        'countries' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/tabella_2_statiesteri.xlsx',
    ]]);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect(app(BirthPlaceRepository::class)->eras(BirthPlaceCode::from('A073')))->toHaveCount(1);
});

test('the suppressed-comuni source URL is configurable', function () {
    fakeUpdatePlacesHttp();
    config(['codicefiscale.sources.suppressed_municipalities' => 'https://situas.istat.it/ShibO2Module/api/Report/Spool/1900-01-01/128']);

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://situas.istat.it/ShibO2Module/api/Report/Spool/1900-01-01/128?PageNum=1'));
});

test('re-running the command leaves the municipalities contents unchanged', function () {
    fakeUpdatePlacesHttp();

    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);
    $firstRun = Municipality::orderBy('code')->orderBy('valid_from')->get(['code', 'name', 'province', 'istat_code', 'valid_from', 'valid_to'])->toArray();
    $this->artisan('codice-fiscale:update-places', ['--municipalities-only' => true])->assertExitCode(0);

    expect($firstRun)->toHaveCount(MUNICIPALITY_ROWS_AFTER_IMPORT)
        ->and(Municipality::orderBy('code')->orderBy('valid_from')->get(['code', 'name', 'province', 'istat_code', 'valid_from', 'valid_to'])->toArray())->toBe($firstRun);
});
