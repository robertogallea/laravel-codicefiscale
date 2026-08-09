<?php

namespace Robertogallea\CodiceFiscale\Contracts;

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;

interface BirthPlaceRepository
{
    /**
     * The era-record valid on the given date (defaulting to today),
     * or null if the code isn't recognized at all, or is recognized
     * but wasn't valid on that date.
     */
    public function find(BirthPlaceCode $code, ?\DateTimeImmutable $on = null): ?BirthPlace;

    /**
     * Whether this code was ever valid, at any point in its history -
     * distinguishes "never a valid code" from "valid code, wrong date".
     */
    public function existedEver(BirthPlaceCode $code): bool;
}
