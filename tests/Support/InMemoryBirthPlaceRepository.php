<?php

namespace Tests\Support;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Support\BirthPlaceEraOrdering;
use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

/**
 * The project's one designed test seam: a BirthPlaceRepository seeded
 * entirely in memory, with no I/O. Core tickets' tests use this
 * instead of the real Eloquent-backed implementation.
 */
final class InMemoryBirthPlaceRepository implements BirthPlaceRepository
{
    /** @var array<string, list<BirthPlace>> */
    private array $recordsByCode = [];

    public function __construct(BirthPlace ...$records)
    {
        foreach ($records as $record) {
            $this->recordsByCode[$record->code()->value()][] = $record;
        }
    }

    public function find(BirthPlaceCode $code, ?\DateTimeImmutable $on = null): ?BirthPlace
    {
        $on ??= new \DateTimeImmutable('today');

        foreach ($this->recordsByCode[$code->value()] ?? [] as $record) {
            if ($record->wasValidOn($on)) {
                return $record;
            }
        }

        return null;
    }

    public function existedEver(BirthPlaceCode $code): bool
    {
        return isset($this->recordsByCode[$code->value()]);
    }

    public function search(string $name, ?\DateTimeImmutable $on = null, ?int $limit = null): array
    {
        $normalizer = new PlaceNameNormalizer();
        $needle = $normalizer->normalize($name);

        $matches = [];

        foreach ($this->recordsByCode as $records) {
            foreach ($records as $record) {
                if ($on !== null && ! $record->wasValidOn($on)) {
                    continue;
                }

                if (str_contains($normalizer->normalize($record->name()), $needle)) {
                    $matches[] = $record;
                }
            }
        }

        return BirthPlaceEraOrdering::sortAndLimit($matches, $limit);
    }
}
