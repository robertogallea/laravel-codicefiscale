<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Support\BirthPlaceEraOrdering;

/**
 * Routes a domestic code to the Municipality-backed repository and a
 * foreign (Z-prefixed) code to the ForeignCountry-backed one.
 */
final class CompositeBirthPlaceRepository implements BirthPlaceRepository
{
    public function __construct(
        private readonly BirthPlaceRepository $domestic,
        private readonly BirthPlaceRepository $foreign,
    ) {
    }

    public function find(BirthPlaceCode $code, ?\DateTimeImmutable $on = null): ?BirthPlace
    {
        return $this->repositoryFor($code)->find($code, $on);
    }

    public function existedEver(BirthPlaceCode $code): bool
    {
        return $this->repositoryFor($code)->existedEver($code);
    }

    public function search(string $name, ?\DateTimeImmutable $on = null, ?int $limit = null): array
    {
        $matches = [
            ...$this->domestic->search($name, $on),
            ...$this->foreign->search($name, $on),
        ];

        return BirthPlaceEraOrdering::sortAndLimit($matches, $limit);
    }

    private function repositoryFor(BirthPlaceCode $code): BirthPlaceRepository
    {
        return $code->isForeign() ? $this->foreign : $this->domestic;
    }
}
