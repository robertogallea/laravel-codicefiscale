<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\CountryCode;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;

/**
 * @property string $code
 * @property string $name
 * @property string $country_code
 * @property \Carbon\CarbonImmutable $valid_from
 * @property \Carbon\CarbonImmutable|null $valid_to
 */
final class ForeignCountry extends AbstractBirthPlaceModel
{
    protected $table = 'foreign_countries';

    protected $fillable = ['code', 'name', 'country_code', 'valid_from', 'valid_to'];

    public function toBirthPlace(): BirthPlace
    {
        return new ForeignBirthPlace(
            code: BirthPlaceCode::from($this->code),
            name: $this->name,
            country: CountryCode::from($this->country_code),
            validFrom: $this->valid_from,
            validTo: $this->valid_to,
        );
    }
}
