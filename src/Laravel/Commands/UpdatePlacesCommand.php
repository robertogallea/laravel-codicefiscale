<?php

namespace Robertogallea\CodiceFiscale\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\DownloadRetryPolicy;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\ForeignCountryXlsxImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\IstatForeignCountryJsonImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\IstatPagedReport;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\IstatSuppressedMunicipalityJsonImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\MunicipalityCsvImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\RetiredForeignCountryImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\RetiredMunicipalityImporter;

class UpdatePlacesCommand extends Command
{
    protected $signature = 'codice-fiscale:update-places
        {--municipalities-only : Only update the Italian municipalities dataset}
        {--countries-only : Only update the foreign countries dataset}';

    protected $description = 'Download and upsert the ANPR comuni archive, ISTAT\'s suppressed-comuni report, the MAECI stati-esteri table and ISTAT\'s historical foreign-countries report';

    public function __construct(
        private readonly MunicipalityCsvImporter $municipalityImporter = new MunicipalityCsvImporter(),
        private readonly ForeignCountryXlsxImporter $countryImporter = new ForeignCountryXlsxImporter(),
        private readonly RetiredForeignCountryImporter $retiredCountryImporter = new RetiredForeignCountryImporter(),
        private readonly IstatForeignCountryJsonImporter $istatCountryImporter = new IstatForeignCountryJsonImporter(),
        private readonly IstatSuppressedMunicipalityJsonImporter $istatMunicipalityImporter = new IstatSuppressedMunicipalityJsonImporter(),
        private readonly RetiredMunicipalityImporter $retiredMunicipalityImporter = new RetiredMunicipalityImporter(),
        private readonly IstatPagedReport $istatPagedReport = new IstatPagedReport(),
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $countriesOnly = (bool) $this->option('countries-only');
        $municipalitiesOnly = (bool) $this->option('municipalities-only');

        if (! $countriesOnly) {
            $this->updateMunicipalities();
        }

        if (! $municipalitiesOnly) {
            $this->updateCountries();
        }

        return self::SUCCESS;
    }

    private function updateMunicipalities(): void
    {
        $this->info('Downloading ANPR comuni archive...');
        $csv = $this->download($this->sourceUrl('municipalities'));

        $this->info('Downloading ISTAT suppressed-comuni report...');
        $json = $this->istatPagedReport->fetch($this->sourceUrl('suppressed_municipalities'));

        // ANPR wins a code outright, then ISTAT's suppressed comuni,
        // then the retired list: each later writer skips the codes an
        // earlier one carries and drops its own stale rows for them
        // (ADR-0012, ADR-0015).
        $count = $this->municipalityImporter->import($csv);
        $anprEras = $this->municipalityImporter->eraStarts($csv);
        $istatCount = $this->istatMunicipalityImporter->import($json, $anprEras);
        $retiredCount = $this->retiredMunicipalityImporter->import(
            $anprEras + $this->istatMunicipalityImporter->eraStarts($json),
        );

        $this->info("Imported {$count} municipality era-records from ANPR.");
        $this->info("Imported {$istatCount} suppressed comuni ANPR lacks from ISTAT.");
        $this->info("Wrote {$retiredCount} retired domestic codes no source carries.");
    }

    private function updateCountries(): void
    {
        $this->info('Downloading MAECI stati-esteri table...');
        $xlsx = $this->download($this->sourceUrl('countries'));

        $this->info('Downloading ISTAT historical foreign-countries report...');
        $json = $this->download($this->sourceUrl('historical_countries'));

        // Plain upserts on the shared (code, valid_from) key, so the
        // last writer of a code wins (ADR-0014): every source beats
        // the retired list (RetiredForeignCountryImporter), MAECI beats
        // ISTAT's current rows, and ISTAT's historical rows beat MAECI
        // where MAECI reassigned a ceased entity's code (Z501).
        $retiredCount = $this->retiredCountryImporter->import();
        $istatCount = $this->istatCountryImporter->importCurrent($json);
        $count = $this->countryImporter->import($xlsx);
        $istatCount += $this->istatCountryImporter->importHistorical($json);

        $this->info("Imported {$count} foreign country records from MAECI.");
        $this->info("Imported {$istatCount} foreign country records from ISTAT.");
        $this->info("Wrote {$retiredCount} retired foreign codes no source carries.");
    }

    private function download(string $url): string
    {
        return Http::retry(
            DownloadRetryPolicy::ATTEMPTS,
            DownloadRetryPolicy::DELAY_MILLISECONDS,
            DownloadRetryPolicy::isTransient(...),
        )->get($url)->throw()->body();
    }

    private function sourceUrl(string $key): string
    {
        // A config published by an older version replaces the whole
        // `sources` array (mergeConfigFrom merges top-level keys only),
        // so a source added since then falls back to the package default.
        $url = config("codicefiscale.sources.$key") ?? $this->defaultSourceUrl($key);

        if (! is_string($url) || $url === '') {
            throw new \RuntimeException("codicefiscale.sources.$key is not configured.");
        }

        return $url;
    }

    private function defaultSourceUrl(string $key): mixed
    {
        /** @var array{sources?: array<string, mixed>} $defaults */
        $defaults = require dirname(__DIR__, 3).'/config/codicefiscale.php';

        return $defaults['sources'][$key] ?? null;
    }
}
