<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models;

use Illuminate\Database\Eloquent\Model;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\ConvertsToBirthPlace;

/**
 * Shared connection/timestamps/casts boilerplate for Municipality and
 * ForeignCountry - the concept of a "row valid on a [validFrom,
 * validTo) window" is common to both, even though their own data
 * columns (province/istat_code vs country_code) are not.
 */
abstract class AbstractBirthPlaceModel extends Model implements ConvertsToBirthPlace
{
    protected $connection = 'codicefiscale';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'valid_from' => 'immutable_date',
            'valid_to' => 'immutable_date',
        ];
    }
}
