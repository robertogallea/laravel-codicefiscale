<?php

namespace Robertogallea\CodiceFiscale\Parsing;

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Contracts\CenturyResolver;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\DateEncoder;
use Robertogallea\CodiceFiscale\Omocodia\Omocodia;
use Robertogallea\CodiceFiscale\Parsing\Century\AgeBasedCenturyResolver;

final class Parser
{
    public function __construct(
        private readonly BirthPlaceRepository $birthPlaceRepository,
        private readonly CenturyResolver $centuryResolver = new AgeBasedCenturyResolver(maxAge: 120),
        private readonly Omocodia $omocodia = new Omocodia(),
    ) {
    }

    /**
     * Field layout of the canonical (de-omocodized) 16-character
     * string, matching CodiceFiscale::STRUCTURE_PATTERN's own
     * breakdown: 3 surname-code letters (0-2), 3 name-code letters
     * (3-5), 2 year digits (6-7), 1 month letter (8), 2 day digits
     * (9-10), then the 4-character birthplace code (11-14).
     */
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
            // CodiceFiscale::STRUCTURE_PATTERN already guarantees position 8
            // is one of the 12 valid month letters, so this is always found.
            birthMonthNumber: (int) array_search($canonical[8], DateEncoder::MONTH_LETTERS, true),
            birthDay: $gender === Gender::Female ? $rawDay - 40 : $rawDay,
            gender: $gender,
            birthPlaceCode: BirthPlaceCode::from(substr($canonical, 11, 4)),
            isOmocodia: $this->omocodia->level($cf) > 0,
            birthPlaceRepository: $this->birthPlaceRepository,
            centuryResolver: $this->centuryResolver,
        );
    }
}
