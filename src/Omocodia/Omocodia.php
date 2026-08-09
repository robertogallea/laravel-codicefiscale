<?php

namespace Robertogallea\CodiceFiscale\Omocodia;

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Generation\Checksum;

/**
 * Handles omocodia: the Agenzia delle Entrate's collision-resolution
 * scheme, which substitutes a subset of 7 fixed digit positions
 * (year, day, birthplace-code digits) with corresponding letters.
 * Any of the 2^7 = 128 subsets may be substituted independently -
 * omocodia is a per-position combination, not a cumulative level.
 */
final class Omocodia
{
    /**
     * The 7 omocodia-eligible positions (0-indexed within the
     * 16-character code): year digits, day digits, then the last 3
     * digits of the birthplace code. The birthplace-code prefix
     * letter (position 11) is never substituted.
     */
    private const ELIGIBLE_POSITIONS = [6, 7, 9, 10, 12, 13, 14];

    private const DIGIT_TO_LETTER = [
        '0' => 'L', '1' => 'M', '2' => 'N', '3' => 'P', '4' => 'Q',
        '5' => 'R', '6' => 'S', '7' => 'T', '8' => 'U', '9' => 'V',
    ];

    private const LETTER_TO_DIGIT = [
        'L' => '0', 'M' => '1', 'N' => '2', 'P' => '3', 'Q' => '4',
        'R' => '5', 'S' => '6', 'T' => '7', 'U' => '8', 'V' => '9',
    ];

    public function __construct(
        private readonly Checksum $checksum = new Checksum(),
    ) {
    }

    public function canonical(CodiceFiscale $cf): CodiceFiscale
    {
        $characters = str_split($cf->value());

        foreach (self::ELIGIBLE_POSITIONS as $position) {
            $characters[$position] = self::LETTER_TO_DIGIT[$characters[$position]] ?? $characters[$position];
        }

        return $this->rebuild($characters);
    }

    /**
     * The full 2^7 = 128-member set of codes sharing the same
     * person-derived data as $cf, one per combination of digit-or-
     * letter across the 7 eligible positions.
     *
     * @return iterable<CodiceFiscale>
     */
    public function variants(CodiceFiscale $cf): iterable
    {
        $canonicalCharacters = str_split($this->canonical($cf)->value());

        $optionsPerPosition = array_map(
            static fn (int $position): array => [
                $canonicalCharacters[$position],
                self::DIGIT_TO_LETTER[$canonicalCharacters[$position]],
            ],
            self::ELIGIBLE_POSITIONS
        );

        foreach ($this->cartesianProduct($optionsPerPosition) as $combination) {
            $characters = $canonicalCharacters;
            foreach (self::ELIGIBLE_POSITIONS as $index => $position) {
                $characters[$position] = $combination[$index];
            }

            yield $this->rebuild($characters);
        }
    }

    /**
     * @param  list<list<string>>  $optionsPerPosition
     * @return iterable<list<string>>
     */
    private function cartesianProduct(array $optionsPerPosition): iterable
    {
        $combinations = [[]];

        foreach ($optionsPerPosition as $options) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($options as $option) {
                    $next[] = [...$combination, $option];
                }
            }
            $combinations = $next;
        }

        yield from $combinations;
    }

    public function level(CodiceFiscale $cf): int
    {
        $characters = str_split($cf->value());

        $substituted = array_filter(
            self::ELIGIBLE_POSITIONS,
            static fn (int $position): bool => isset(self::LETTER_TO_DIGIT[$characters[$position]])
        );

        return count($substituted);
    }

    /** @param array<int, string> $characters */
    private function rebuild(array $characters): CodiceFiscale
    {
        $first15 = implode('', array_slice($characters, 0, 15));

        return CodiceFiscale::from($first15.$this->checksum->calculate($first15));
    }
}
