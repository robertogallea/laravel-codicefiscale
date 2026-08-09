<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;

/**
 * @property string $code
 * @property string $name
 * @property string $province
 * @property string $istat_code
 * @property \Carbon\CarbonImmutable $valid_from
 * @property \Carbon\CarbonImmutable|null $valid_to
 */
final class Municipality extends AbstractBirthPlaceModel
{
    protected $fillable = ['code', 'name', 'province', 'istat_code', 'valid_from', 'valid_to'];

    public function toBirthPlace(): BirthPlace
    {
        return new DomesticBirthPlace(
            code: BirthPlaceCode::from($this->code),
            name: $this->name,
            province: $this->province,
            istatCode: $this->istat_code,
            validFrom: $this->valid_from,
            validTo: $this->valid_to,
        );
    }
}
