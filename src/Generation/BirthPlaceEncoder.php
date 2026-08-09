<?php

namespace Robertogallea\CodiceFiscale\Generation;

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;

final class BirthPlaceEncoder
{
    public function encode(BirthPlaceCode $birthPlace): string
    {
        return $birthPlace->value();
    }
}
