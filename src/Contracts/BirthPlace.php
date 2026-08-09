<?php

namespace Robertogallea\CodiceFiscale\Contracts;

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;

/**
 * A single time-bounded record describing a BirthPlaceCode during one
 * era: name and the [validFrom, validTo) window during which that
 * name (and, for a domestic record, province) combination held.
 * validTo() is null for a still-currently-valid record.
 */
interface BirthPlace
{
    public function code(): BirthPlaceCode;

    public function name(): string;

    public function validFrom(): \DateTimeImmutable;

    public function validTo(): ?\DateTimeImmutable;

    public function wasValidOn(\DateTimeImmutable $date): bool;
}
