<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CheckForEmptyCode implements Check
{
    /**
     * @throws CodiceFiscaleValidationException
     */
    public function check($code): bool
    {
        if (($code === null) || ($code === '')) {
            throw new CodiceFiscaleValidationException(
                'Invalid codice fiscale',
                CodiceFiscaleValidationException::NO_CODE
            );
        }

        return true;
    }
}
