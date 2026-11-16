<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\IstatPagedReport;

const PAGED_REPORT_URL = 'https://situas.istat.it/ShibO2Module/api/Report/Spool/1861-03-17/128';

/** Three real report-128 rows, in the order the paged endpoint returns them. */
function pagedReportRows(): array
{
    return array_slice(json_decode(file_get_contents(__DIR__.'/../Fixtures/istat_comuni_soppressi_sample.json'), true)['resultset'], 0, 3);
}

/** A page in the paged endpoint's real shape (label/totalRows/body/...). */
function pagedReportPage(array $rows, int $totalRows): string
{
    return json_encode(['label' => ['Anno evento'], 'totalRows' => $totalRows, 'body' => $rows, 'colNames' => ['ANNO'], 'formatMasks' => [], 'types' => []]);
}

beforeEach(function () {
    Sleep::fake();
    Http::preventStrayRequests();
});

test('fetches every page of the report and assembles the rows into one resultset', function () {
    $rows = pagedReportRows();
    Http::fake([
        'situas.istat.it/*PageNum=1&PageSize=2' => Http::response(pagedReportPage(array_slice($rows, 0, 2), 3)),
        'situas.istat.it/*PageNum=2&PageSize=2' => Http::response(pagedReportPage(array_slice($rows, 2), 3)),
    ]);

    $json = (new IstatPagedReport(pageSize: 2))->fetch(PAGED_REPORT_URL);

    expect(json_decode($json, true))->toBe(['resultset' => $rows]);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->body() === '{}'
        && $request->url() === PAGED_REPORT_URL.'?PageNum=1&PageSize=2');
});

test('a page whose connection drops, or whose body is cut short, is retried', function () {
    $rows = pagedReportRows();
    Http::fake([
        'situas.istat.it/*PageNum=1&PageSize=2' => Http::sequence()
            ->pushFailedConnection()
            ->push(pagedReportPage(array_slice($rows, 0, 2), 3)),
        'situas.istat.it/*PageNum=2&PageSize=2' => Http::sequence()
            ->push(substr(pagedReportPage(array_slice($rows, 2), 3), 0, 100))
            ->push(pagedReportPage(array_slice($rows, 2), 3)),
    ]);

    $json = (new IstatPagedReport(pageSize: 2))->fetch(PAGED_REPORT_URL);

    expect(json_decode($json, true)['resultset'])->toBe($rows);
    Http::assertSentCount(4);
});

test('a page that keeps failing fails the fetch', function () {
    Http::fake([
        'situas.istat.it/*' => Http::sequence()->pushFailedConnection()->pushFailedConnection()->pushFailedConnection(),
    ]);

    (new IstatPagedReport(pageSize: 2))->fetch(PAGED_REPORT_URL);
})->throws(ConnectionException::class);

test('a fetch that collects fewer rows than the report declares fails', function () {
    $rows = pagedReportRows();
    Http::fake([
        'situas.istat.it/*PageNum=1&PageSize=2' => Http::response(pagedReportPage(array_slice($rows, 0, 2), 3)),
        'situas.istat.it/*PageNum=2&PageSize=2' => Http::response(pagedReportPage([], 3)),
    ]);

    (new IstatPagedReport(pageSize: 2))->fetch(PAGED_REPORT_URL);
})->throws(RuntimeException::class, 'collected 2 of 3 rows');

test('a report declaring more rows than the page cap allows is refused', function () {
    Http::fake([
        'situas.istat.it/*' => Http::response(pagedReportPage(pagedReportRows(), 10_000_000)),
    ]);

    (new IstatPagedReport(pageSize: 2))->fetch(PAGED_REPORT_URL);
})->throws(RuntimeException::class, 'pages');

test('a page refused with a client error fails at once, without retries', function () {
    Http::fake([
        'situas.istat.it/*' => Http::response('Bad Request', 400),
    ]);

    expect(fn () => (new IstatPagedReport(pageSize: 2))->fetch(PAGED_REPORT_URL))->toThrow(RequestException::class);
    Http::assertSentCount(1);
});

test('a page answered with a server error is retried', function () {
    $rows = pagedReportRows();
    Http::fake([
        'situas.istat.it/*' => Http::sequence()
            ->push('Service Unavailable', 503)
            ->push(pagedReportPage($rows, 3)),
    ]);

    expect(json_decode((new IstatPagedReport(pageSize: 3))->fetch(PAGED_REPORT_URL), true)['resultset'])->toBe($rows);
    Http::assertSentCount(2);
});
