<?php

namespace robertogallea\LaravelCodiceFiscale\Faker;

use Carbon\Carbon;
use Faker\Provider\Base;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ItalianCitiesStaticList;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleConfig;

class CodiceFiscaleFakerProvider extends Base
{
    public function codiceFiscale(?string $firstName = null, ?string $lastName = null, Carbon|string|null $birthDate = null, ?string $gender = null, ?string $birthPlace = null): string
    {
        $cfConfig = resolve(CodiceFiscaleConfig::class);

        $firstName ??= $this->generator->firstName();
        $lastName ??= $this->generator->lastName();
        $birthDate ??= $this->generator->date();
        $birthPlace ??= static::randomElement(ItalianCitiesStaticList::getList());
        $gender ??= static::randomElement([$cfConfig->getMaleLabel(), $cfConfig->getFemaleLabel()]);

        return CodiceFiscale::generate($firstName, $lastName, $birthDate, $birthPlace, $gender);
    }
}
