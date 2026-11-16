<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Illuminate\Support\Facades\Http;

/**
 * Fetches an ISTAT Situas report through the paged endpoint the Situas
 * web app uses (`.../ShibO2Module/api/Report/Spool/{date}/{report}`,
 * POST, `PageNum`/`PageSize`), and returns it in the shape of the
 * public `reportspooljson` endpoint: `{"resultset": [...rows]}`.
 *
 * The public endpoint can't deliver a large report: on 2026-11-16 it
 * cut report 128 (about 850 KB) short in five downloads out of six.
 * Pages of a few hundred rows arrive whole. Each page is retried on
 * its own (DownloadRetryPolicy), a body cut short counts as a
 * transient failure, and the fetch fails unless it collects exactly
 * the number of rows the report declares (ADR-0015).
 */
final class IstatPagedReport
{
    /** Guards against a changed API sending the fetch round in circles. */
    private const MAX_PAGES = 200;

    public function __construct(
        private readonly int $pageSize = 200,
    ) {
    }

    public function fetch(string $baseUrl): string
    {
        $first = $this->page($baseUrl, 1);
        $totalRows = $first['totalRows'];
        $pages = max(1, (int) ceil($totalRows / $this->pageSize));

        if ($pages > self::MAX_PAGES) {
            throw new \RuntimeException("The ISTAT report at \"$baseUrl\" declares {$totalRows} rows, more than ".self::MAX_PAGES." pages of {$this->pageSize}.");
        }

        $rows = $first['body'];

        for ($pageNum = 2; $pageNum <= $pages; $pageNum++) {
            $rows = [...$rows, ...$this->page($baseUrl, $pageNum)['body']];
        }

        if (count($rows) !== $totalRows) {
            throw new \RuntimeException("The ISTAT report at \"$baseUrl\" collected ".count($rows)." of {$totalRows} rows.");
        }

        return json_encode(['resultset' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{totalRows: int, body: list<mixed>}
     */
    private function page(string $baseUrl, int $pageNum): array
    {
        $url = $baseUrl.'?'.http_build_query(['PageNum' => $pageNum, 'PageSize' => $this->pageSize]);

        return retry(
            DownloadRetryPolicy::ATTEMPTS,
            function () use ($url): array {
                // The endpoint answers 400 to an empty JSON array and 411 to no body.
                $decoded = Http::withBody('{}', 'application/json')->post($url)->throw()->json();

                if (! is_array($decoded) || ! is_int($decoded['totalRows'] ?? null) || ! is_array($decoded['body'] ?? null) || ! array_is_list($decoded['body'])) {
                    throw new IncompleteReportPage("The ISTAT report page \"$url\" is not a complete JSON page.");
                }

                return ['totalRows' => $decoded['totalRows'], 'body' => $decoded['body']];
            },
            DownloadRetryPolicy::DELAY_MILLISECONDS,
            DownloadRetryPolicy::isTransient(...),
        );
    }
}
