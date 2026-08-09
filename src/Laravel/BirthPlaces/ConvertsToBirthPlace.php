<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;

/**
 * Implemented by each Eloquent model backing a BirthPlaceRepository,
 * so EloquentBirthPlaceRepository can map a row to its domain object
 * without needing to know which concrete model it's querying.
 */
interface ConvertsToBirthPlace
{
    public function toBirthPlace(): BirthPlace;
}
