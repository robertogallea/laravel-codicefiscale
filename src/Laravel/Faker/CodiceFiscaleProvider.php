<?php

namespace Robertogallea\CodiceFiscale\Laravel\Faker;

use DateTimeImmutable;
use Faker\Provider\Base;
use Faker\Provider\Person;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\Person as CodiceFiscalePerson;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\Generator;

/**
 * Generates a random, structurally/checksum-valid CodiceFiscale via
 * the real Person/Generator API - never a hand-rolled string. Draws
 * its birthplace from knownBirthPlaces(): a small, fixed set of major
 * Italian municipalities' *current* cadastral-code eras, sourced from
 * ANPR's own comuni archive but deliberately not a copy of the full
 * downloadable dataset (ADR-0003 forbids bundling that). Works with
 * no database access at all, so it never depends on
 * codice-fiscale:update-places having been run first.
 */
final class CodiceFiscaleProvider extends Base
{
    /** @return list<DomesticBirthPlace> */
    public static function knownBirthPlaces(): array
    {
        return [
            new DomesticBirthPlace(BirthPlaceCode::from('H501'), 'ROMA', 'RM', '058091', new DateTimeImmutable('1992-04-04')),
            new DomesticBirthPlace(BirthPlaceCode::from('F205'), 'MILANO', 'MI', '015146', new DateTimeImmutable('1997-07-26')),
            new DomesticBirthPlace(BirthPlaceCode::from('F839'), 'NAPOLI', 'NA', '063049', new DateTimeImmutable('1929-04-13')),
            new DomesticBirthPlace(BirthPlaceCode::from('L219'), 'TORINO', 'TO', '001272', new DateTimeImmutable('1889-08-12')),
            new DomesticBirthPlace(BirthPlaceCode::from('G273'), 'PALERMO', 'PA', '082053', new DateTimeImmutable('1929-06-18')),
            new DomesticBirthPlace(BirthPlaceCode::from('D969'), 'GENOVA', 'GE', '010025', new DateTimeImmutable('1926-02-06')),
            new DomesticBirthPlace(BirthPlaceCode::from('A944'), 'BOLOGNA', 'BO', '037006', new DateTimeImmutable('1937-12-06')),
            new DomesticBirthPlace(BirthPlaceCode::from('D612'), 'FIRENZE', 'FI', '048017', new DateTimeImmutable('1939-11-15')),
            new DomesticBirthPlace(BirthPlaceCode::from('A662'), 'BARI', 'BA', '072006', new DateTimeImmutable('1937-04-02')),
            new DomesticBirthPlace(BirthPlaceCode::from('L736'), 'VENEZIA', 'VE', '027042', new DateTimeImmutable('1999-04-17')),
        ];
    }

    public function codiceFiscale(): string
    {
        return (new Generator())->generate($this->randomPerson())->value();
    }

    /**
     * Not Base::randomElement(): its untyped mixed return can't be
     * narrowed back to DomesticBirthPlace/Gender, so array_rand() is
     * used directly on these already-typed arrays instead. Still
     * deterministic under Faker's own seed() - it calls the global
     * mt_srand(), which array_rand() also draws from.
     */
    private function randomPerson(): CodiceFiscalePerson
    {
        $birthPlaces = self::knownBirthPlaces();
        $birthPlace = $birthPlaces[array_rand($birthPlaces)];

        $genders = Gender::cases();
        $gender = $genders[array_rand($genders)];

        return new CodiceFiscalePerson(
            firstName: $this->generator->firstName($gender === Gender::Male ? Person::GENDER_MALE : Person::GENDER_FEMALE),
            lastName: $this->generator->lastName(),
            birthDate: $this->randomBirthDate($birthPlace->validFrom()),
            birthPlace: $birthPlace->code(),
            gender: $gender,
        );
    }

    /**
     * Bounded to the last 100 years (well within
     * AgeBasedCenturyResolver's default 120-year window, so a
     * round-trip parse never lands on the wrong century) and never
     * before the birthplace's own current era began.
     */
    private function randomBirthDate(DateTimeImmutable $validFrom): DateTimeImmutable
    {
        $hundredYearsAgo = new DateTimeImmutable('-100 years');
        $lowerBound = $validFrom > $hundredYearsAgo ? $validFrom : $hundredYearsAgo;

        return DateTimeImmutable::createFromMutable(
            $this->generator->dateTimeBetween($lowerBound->format('Y-m-d'), 'now'),
        );
    }
}
