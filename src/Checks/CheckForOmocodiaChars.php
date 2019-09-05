<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CheckForOmocodiaChars implements Check
{
    protected $tabReplacementOmocodia = [6, 7, 9, 10, 12, 13, 14];

    protected $tabDecodeOmocodia = [
        'A' => '!',
        'B' => '!',
        'C' => '!',
        'D' => '!',
        'E' => '!',
        'F' => '!',
        'G' => '!',
        'H' => '!',
        'I' => '!',
        'J' => '!',
        'K' => '!',
        'L' => '0',
        'M' => '1',
        'N' => '2',
        'O' => '!',
        'P' => '3',
        'Q' => '4',
        'R' => '5',
        'S' => '6',
        'T' => '7',
        'U' => '8',
        'V' => '9',
        'W' => '!',
        'X' => '!',
        'Y' => '!',
        'Z' => '!',
    ];

    public function check($code)
    {
        $cfArray = str_split($code);

        for ($i = 0; $i < count($this->tabReplacementOmocodia); $i++) {
            if ((!is_numeric($cfArray[$this->tabReplacementOmocodia[$i]])) &&
                ($this->tabDecodeOmocodia[$cfArray[$this->tabReplacementOmocodia[$i]]] === '!')) {
                throw new CodiceFiscaleValidationException('Invalid codice fiscale',
                    CodiceFiscaleValidationException::BAD_OMOCODIA_CHAR);
            }
        }

        return true;
    }
}
