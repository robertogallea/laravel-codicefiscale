<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CheckForWrongSize implements Check
{
    public function check($code)
    {
        if (strlen($code) !== 16) {
            throw new CodiceFiscaleValidationException('Invalid codice fiscale',
                CodiceFiscaleValidationException::WRONG_SIZE);
        }

        return true;
    }
}
