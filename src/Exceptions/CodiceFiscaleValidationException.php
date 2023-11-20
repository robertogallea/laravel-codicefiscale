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

    public const WRONG_FIRST_NAME = 9;
    public const WRONG_LAST_NAME = 10;
    public const WRONG_BIRTH_DAY = 11;
    public const WRONG_BIRTH_MONTH = 12;
    public const WRONG_BIRTH_YEAR = 13;
    public const WRONG_BIRTH_PLACE = 14;
    public const WRONG_GENDER = 15;
}
