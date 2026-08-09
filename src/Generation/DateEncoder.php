<?php

namespace Robertogallea\CodiceFiscale\Generation;

use Robertogallea\CodiceFiscale\Enums\Gender;

final class DateEncoder
{
    /**
     * The single source of truth for the month letter table - also
     * referenced by Parser to decode a letter back into a month
     * number, so a correction here propagates to both directions.
     */
    public const MONTH_LETTERS = [
        1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E',
        6 => 'H', 7 => 'L', 8 => 'M', 9 => 'P', 10 => 'R',
        11 => 'S', 12 => 'T',
    ];

    public function encode(\DateTimeImmutable $birthDate, Gender $gender): string
    {
        $year = substr($birthDate->format('Y'), -2);
        $month = self::MONTH_LETTERS[(int) $birthDate->format('n')];

        $day = (int) $birthDate->format('j');
        if ($gender === Gender::Female) {
            $day += 40;
        }

        return $year.$month.str_pad((string) $day, 2, '0', STR_PAD_LEFT);
    }
}
