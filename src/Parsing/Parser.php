<?php

namespace Robertogallea\CodiceFiscale\Parsing;

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Contracts\CenturyResolver;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Omocodia\Omocodia;
use Robertogallea\CodiceFiscale\Parsing\Century\AgeBasedCenturyResolver;

final class Parser
{
    private const MONTH_LETTER_TO_NUMBER = [
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5,
        'H' => 6, 'L' => 7, 'M' => 8, 'P' => 9, 'R' => 10,
        'S' => 11, 'T' => 12,
    ];

    public function __construct(
        private readonly BirthPlaceRepository $birthPlaceRepository,
        private readonly CenturyResolver $centuryResolver = new AgeBasedCenturyResolver(maxAge: 120),
        private readonly Omocodia $omocodia = new Omocodia(),
    ) {
    }

    public function parse(CodiceFiscale $cf): ParsedCodiceFiscale
    {
        // De-omocodize first: the 7 eligible positions might hold a
        // letter instead of a digit, and every numeric field below
        // (year, day, birthplace digits) needs the real digit.
        $canonical = $this->omocodia->canonical($cf)->value();

        $rawDay = (int) substr($canonical, 9, 2);
        $gender = $rawDay > 40 ? Gender::Female : Gender::Male;

        return new ParsedCodiceFiscale(
            surnameCode: substr($canonical, 0, 3),
            nameCode: substr($canonical, 3, 3),
            birthYearCode: (int) substr($canonical, 6, 2),
            birthMonthNumber: self::MONTH_LETTER_TO_NUMBER[$canonical[8]],
            birthDay: $gender === Gender::Female ? $rawDay - 40 : $rawDay,
            gender: $gender,
            birthPlaceCode: BirthPlaceCode::from(substr($canonical, 11, 4)),
            isOmocodia: $this->omocodia->level($cf) > 0,
            birthPlaceRepository: $this->birthPlaceRepository,
            centuryResolver: $this->centuryResolver,
        );
    }
}
