<?php

namespace Robertogallea\CodiceFiscale\Enums;

enum ValidationError: string
{
    case InvalidFormat = 'invalid_format';
    case InvalidChecksum = 'invalid_checksum';
    case InvalidDate = 'invalid_date';
    case UnknownBirthPlace = 'unknown_birth_place';
    case BirthPlaceNotValidOnDate = 'birth_place_not_valid_on_date';
}
