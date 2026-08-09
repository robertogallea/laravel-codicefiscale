<?php

namespace Robertogallea\CodiceFiscale\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\ForeignCountryXlsxImporter;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import\MunicipalityCsvImporter;

class UpdatePlacesCommand extends Command
{
    protected $signature = 'codice-fiscale:update-places
        {--municipalities-only : Only update the Italian municipalities dataset}
        {--countries-only : Only update the foreign countries dataset}';

    protected $description = 'Download and upsert the ANPR comuni archive and MAECI stati-esteri table';

    public function __construct(
        private readonly MunicipalityCsvImporter $municipalityImporter = new MunicipalityCsvImporter(),
        private readonly ForeignCountryXlsxImporter $countryImporter = new ForeignCountryXlsxImporter(),
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
        $count = $this->municipalityImporter->import($csv);

        $this->info("Imported {$count} municipality era-records.");
    }

    private function updateCountries(): void
    {
        $this->info('Downloading MAECI stati-esteri table...');

        $xlsx = $this->download($this->sourceUrl('countries'));
        $count = $this->countryImporter->import($xlsx);

        $this->info("Imported {$count} foreign country records.");
    }

    private function download(string $url): string
    {
        $response = Http::get($url);

        if (! $response instanceof Response) {
            throw new \RuntimeException("Downloading \"$url\" did not return a response (async requests aren't used here).");
        }

        return $response->throw()->body();
    }

    private function sourceUrl(string $key): string
    {
        $url = config("codicefiscale.sources.$key");

        if (! is_string($url) || $url === '') {
            throw new \RuntimeException("codicefiscale.sources.$key is not configured.");
        }

        return $url;
    }
}
