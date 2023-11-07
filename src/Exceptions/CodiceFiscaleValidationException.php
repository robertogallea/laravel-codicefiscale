<?php

namespace robertogallea\LaravelCodiceFiscale\Exceptions;

class CodiceFiscaleValidationException extends \Exception
{
    public const NO_ERROR = 0;

    public const NO_CODE = 1;

    public const WRONG_SIZE = 2;

    public const BAD_CHARACTERS = 3;

    public const BAD_OMOCODIA_CHAR = 4;

    public const WRONG_CODE = 5;

    public const MISSING_CITY_CODE = 6;

    public const NO_MATCH = 7;

    public const EMPTY_BIRTHDATE = 8;
}
