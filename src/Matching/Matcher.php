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
        } else {
            $expected = $this->nameEncoder->surnameCode($this->nameNormalizer->normalize($person->lastName));
            $this->record($expected === $parsed->surnameCode(), PersonField::LastName, $matched, $mismatched);
        }

        if ($person->firstName === null) {
            $skipped[] = PersonField::FirstName;
        } else {
            $expected = $this->nameEncoder->nameCode($this->nameNormalizer->normalize($person->firstName));
            $this->record($expected === $parsed->nameCode(), PersonField::FirstName, $matched, $mismatched);
        }

        if ($person->birthDate === null) {
            $skipped[] = PersonField::BirthDate;
        } else {
            $parsedDate = $parsed->birthDate();
            $isMatch = $parsedDate !== null && $parsedDate->format('Y-m-d') === $person->birthDate->format('Y-m-d');
            $this->record($isMatch, PersonField::BirthDate, $matched, $mismatched);
        }

        if ($person->birthPlace === null) {
            $skipped[] = PersonField::BirthPlace;
        } else {
            $this->record($person->birthPlace->equals($parsed->birthPlaceCode()), PersonField::BirthPlace, $matched, $mismatched);
        }

        if ($person->gender === null) {
            $skipped[] = PersonField::Gender;
        } else {
            $this->record($person->gender === $parsed->gender(), PersonField::Gender, $matched, $mismatched);
        }

        return new MatchResult($matched, $mismatched, $skipped);
    }

    /**
     * @param  list<PersonField>  $matched
     * @param  list<PersonField>  $mismatched
     */
    private function record(bool $isMatch, PersonField $field, array &$matched, array &$mismatched): void
    {
        if ($isMatch) {
            $matched[] = $field;
        } else {
            $mismatched[] = $field;
        }
    }
}
