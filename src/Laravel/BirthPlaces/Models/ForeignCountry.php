<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models;

use Illuminate\Database\Eloquent\Model;
use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\CountryCode;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\ConvertsToBirthPlace;

/**
 * @property string $code
 * @property string $name
 * @property string $country_code
 * @property \Carbon\CarbonImmutable $valid_from
 * @property \Carbon\CarbonImmutable|null $valid_to
 */
final class ForeignCountry extends Model implements ConvertsToBirthPlace
{
    protected $connection = 'codicefiscale';

    public $timestamps = false;

    protected $table = 'foreign_countries';

    protected $fillable = ['code', 'name', 'country_code', 'valid_from', 'valid_to'];

    protected function casts(): array
    {
        return [
            'valid_from' => 'immutable_date',
            'valid_to' => 'immutable_date',
        ];
    }

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
