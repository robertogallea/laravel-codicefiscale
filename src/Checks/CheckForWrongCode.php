<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CheckForWrongCode implements Check
{
    protected $tabEvenChars = [
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 0,
        'B' => 1,
        'C' => 2,
        'D' => 3,
        'E' => 4,
        'F' => 5,
        'G' => 6,
        'H' => 7,
        'I' => 8,
        'J' => 9,
        'K' => 10,
        'L' => 11,
        'M' => 12,
        'N' => 13,
        'O' => 14,
        'P' => 15,
        'Q' => 16,
        'R' => 17,
        'S' => 18,
        'T' => 19,
        'U' => 20,
        'V' => 21,
        'W' => 22,
        'X' => 23,
        'Y' => 24,
        'Z' => 25,
    ];

    protected $tabOddChars = [
        '0' => 1,
        '1' => 0,
        '2' => 5,
        '3' => 7,
        '4' => 9,
        '5' => 13,
        '6' => 15,
        '7' => 17,
        '8' => 19,
        '9' => 21,
        'A' => 1,
        'B' => 0,
        'C' => 5,
        'D' => 7,
        'E' => 9,
        'F' => 13,
        'G' => 15,
        'H' => 17,
        'I' => 19,
        'J' => 21,
        'K' => 2,
        'L' => 4,
        'M' => 18,
        'N' => 20,
        'O' => 11,
        'P' => 3,
        'Q' => 6,
        'R' => 8,
        'S' => 12,
        'T' => 14,
        'U' => 16,
        'V' => 10,
        'W' => 22,
        'X' => 25,
        'Y' => 24,
        'Z' => 23,
    ];

    protected $tabControlCode = [
        0  => 'A',
        1  => 'B',
        2  => 'C',
        3  => 'D',
        4  => 'E',
        5  => 'F',
        6  => 'G',
        7  => 'H',
        8  => 'I',
        9  => 'J',
        10 => 'K',
        11 => 'L',
        12 => 'M',
        13 => 'N',
        14 => 'O',
        15 => 'P',
        16 => 'Q',
        17 => 'R',
        18 => 'S',
        19 => 'T',
        20 => 'U',
        21 => 'V',
        22 => 'W',
        23 => 'X',
        24 => 'Y',
        25 => 'Z',
    ];

    public function check($code)
    {
        $cfArray = str_split($code);

        $even = 0;
        $odd = $this->tabOddChars[$cfArray[14]];

        for ($i = 0; $i < 13; $i += 2) {
            $odd = $odd + $this->tabOddChars[$cfArray[$i]];
            $even = $even + $this->tabEvenChars[$cfArray[$i + 1]];
        }

        if (!($this->tabControlCode[($even + $odd) % 26] === $cfArray[15]) || (!$this->checkRegex($code))) {
            throw new CodiceFiscaleValidationException(
                'Invalid codice fiscale',
                CodiceFiscaleValidationException::WRONG_CODE
            );
        }

        return true;
    }
}
