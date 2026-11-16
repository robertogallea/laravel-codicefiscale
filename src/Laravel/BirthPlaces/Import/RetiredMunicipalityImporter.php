<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\RetiredBirthPlaces;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

/**
 * Writes the retired domestic codes no downloadable source carries
 * (RetiredBirthPlaces::domestic(), ADR-0012) into the municipalities
 * table, after the downloaded sources. A code a source carries is
 * skipped, and the bundled row an earlier run wrote for it is removed,
 * so the source wins outright and corrections to the list still reach
 * existing installations on the next run.
 */
final class RetiredMunicipalityImporter
{
    use DefersToHigherPrioritySources;
    use UpsertsInChunks;

    private const CHUNK_SIZE = 100;

    public function __construct(
        private readonly PlaceNameNormalizer $normalizer = new PlaceNameNormalizer(),
    ) {
    }

    /**
     * @param  array<string, list<string>>  $sourceEras  code => valid_from of every era the downloaded domestic sources carry
     */
    public function import(array $sourceEras = []): int
    {
        $places = RetiredBirthPlaces::domestic();

        $this->deleteRowsSupersededBySource(
            array_map(static fn (DomesticBirthPlace $place): string => $place->code()->value(), $places),
            $sourceEras,
        );

        return $this->upsertInChunks(
            array_values(array_filter(
                $places,
                static fn (DomesticBirthPlace $place): bool => ! isset($sourceEras[$place->code()->value()]),
            )),
            $this->toRecord(...),
            Municipality::class,
            ['code', 'valid_from'],
            ['name', 'province', 'istat_code', 'valid_to', 'name_normalized'],
            self::CHUNK_SIZE,
        );
    }

    /** @return array<string, string|null> */
    private function toRecord(DomesticBirthPlace $place): array
    {
        return [
            'code' => $place->code()->value(),
            'name' => $place->name(),
            'province' => $place->province(),
            'istat_code' => $place->istatCode(),
            'valid_from' => $place->validFrom()->format('Y-m-d'),
            'valid_to' => $place->validTo()?->format('Y-m-d'),
            'name_normalized' => $this->normalizer->normalize($place->name()),
        ];
    }
}
