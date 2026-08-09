<?php

namespace Robertogallea\CodiceFiscale\Matching;

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\NameNormalizer;
use Robertogallea\CodiceFiscale\Data\PartialPerson;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\PersonField;
use Robertogallea\CodiceFiscale\Generation\ItalianNameNormalizer;
use Robertogallea\CodiceFiscale\Generation\NameEncoder;
use Robertogallea\CodiceFiscale\Parsing\Parser;

final class Matcher
{
    public function __construct(
        private readonly Parser $parser,
        private readonly NameNormalizer $nameNormalizer = new ItalianNameNormalizer(),
        private readonly NameEncoder $nameEncoder = new NameEncoder(),
    ) {
    }

    public function match(CodiceFiscale $cf, Person|PartialPerson $person): MatchResult
    {
        $parsed = $this->parser->parse($cf);

        $matched = [];
        $mismatched = [];
        $skipped = [];

        if ($person->lastName === null) {
            $skipped[] = PersonField::LastName;
        } elseif ($this->nameEncoder->surnameCode($this->nameNormalizer->normalize($person->lastName)) === $parsed->surnameCode()) {
            $matched[] = PersonField::LastName;
        } else {
            $mismatched[] = PersonField::LastName;
        }

        if ($person->firstName === null) {
            $skipped[] = PersonField::FirstName;
        } elseif ($this->nameEncoder->nameCode($this->nameNormalizer->normalize($person->firstName)) === $parsed->nameCode()) {
            $matched[] = PersonField::FirstName;
        } else {
            $mismatched[] = PersonField::FirstName;
        }

        if ($person->birthDate === null) {
            $skipped[] = PersonField::BirthDate;
        } elseif ($parsed->birthDate()?->format('Y-m-d') === $person->birthDate->format('Y-m-d')) {
            $matched[] = PersonField::BirthDate;
        } else {
            $mismatched[] = PersonField::BirthDate;
        }

        if ($person->birthPlace === null) {
            $skipped[] = PersonField::BirthPlace;
        } elseif ($person->birthPlace->equals($parsed->birthPlaceCode())) {
            $matched[] = PersonField::BirthPlace;
        } else {
            $mismatched[] = PersonField::BirthPlace;
        }

        if ($person->gender === null) {
            $skipped[] = PersonField::Gender;
        } elseif ($person->gender === $parsed->gender()) {
            $matched[] = PersonField::Gender;
        } else {
            $mismatched[] = PersonField::Gender;
        }

        return new MatchResult($matched, $mismatched, $skipped);
    }
}
