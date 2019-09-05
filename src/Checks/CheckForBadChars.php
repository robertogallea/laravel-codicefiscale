<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CheckForBadChars implements Check
{
    public function check($code)
    {
        $code = strtoupper($code);

        if (!preg_match('/^[A-Z0-9]+$/', $code)) {
            throw new CodiceFiscaleValidationException('Invalid codice fiscale',
                CodiceFiscaleValidationException::BAD_CHARACTERS);
        }

        return true;
    }
}
