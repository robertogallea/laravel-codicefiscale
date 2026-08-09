<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models;

use Illuminate\Database\Eloquent\Model;
use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\ConvertsToBirthPlace;

/**
 * @property string $code
 * @property string $name
 * @property string $province
 * @property string $istat_code
 * @property \Carbon\CarbonImmutable $valid_from
 * @property \Carbon\CarbonImmutable|null $valid_to
 */
final class Municipality extends Model implements ConvertsToBirthPlace
{
    protected $connection = 'codicefiscale';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'province', 'istat_code', 'valid_from', 'valid_to'];

    protected function casts(): array
    {
        return [
            'valid_from' => 'immutable_date',
            'valid_to' => 'immutable_date',
        ];
    }

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
