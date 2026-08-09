<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;

/**
 * Shared [validFrom, validTo) bookkeeping for DomesticBirthPlace and
 * ForeignBirthPlace. Holds only what both eras genuinely have in
 * common — a province/istatCode/country field does not belong here,
 * since that split is exactly what distinguishes the two subtypes.
 */
abstract readonly class AbstractBirthPlace implements BirthPlace
{
    public function __construct(
        private BirthPlaceCode $code,
        private string $name,
        private \DateTimeImmutable $validFrom,
        private ?\DateTimeImmutable $validTo = null,
    ) {
    }

    public function code(): BirthPlaceCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function validFrom(): \DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function validTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function wasValidOn(\DateTimeImmutable $date): bool
    {
        return $date >= $this->validFrom
            && ($this->validTo === null || $date < $this->validTo);
    }
}
