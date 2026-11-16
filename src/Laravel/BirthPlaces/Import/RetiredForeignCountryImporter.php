<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Data\RetiredBirthPlaces;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

/**
 * Writes the retired foreign codes no downloadable source carries
 * (RetiredBirthPlaces, ADR-0012) into the foreign_countries table.
 *
 * Runs *before* the downloaded sources: it upserts on the same
 * (code, valid_from) key the MAECI importer uses, and every list entry
 * uses MAECI's 1900-01-01 valid_from, so a source that carries one of
 * these codes simply overwrites the row - the source wins with no
 * provenance tracking, and corrections to the list still reach
 * existing installations on the next run. A future source importer
 * must keep the same valid_from convention for this to hold.
 */
final class RetiredForeignCountryImporter
{
    use UpsertsInChunks;

    private const CHUNK_SIZE = 100;

    public function __construct(
        private readonly PlaceNameNormalizer $normalizer = new PlaceNameNormalizer(),
    ) {
    }

    public function import(): int
    {
        return $this->upsertInChunks(
            RetiredBirthPlaces::foreign(),
            $this->toRecord(...),
            ForeignCountry::class,
            ['code', 'valid_from'],
            ['name', 'country_code', 'valid_to', 'name_normalized'],
            self::CHUNK_SIZE,
        );
    }

    /** @return array<string, string|null> */
    private function toRecord(ForeignBirthPlace $place): array
    {
        return [
            'code' => $place->code()->value(),
            'name' => $place->name(),
            'country_code' => $place->country()->value(),
            'valid_from' => $place->validFrom()->format('Y-m-d'),
            'valid_to' => $place->validTo()?->format('Y-m-d'),
            'name_normalized' => $this->normalizer->normalize($place->name()),
        ];
    }
}
