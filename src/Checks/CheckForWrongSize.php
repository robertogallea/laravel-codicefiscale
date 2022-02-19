<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CheckForWrongSize implements Check
{
    /**
     * @throws CodiceFiscaleValidationException
     */
    public function check($code): bool
    {
        if (strlen($code) !== 16) {
            throw new CodiceFiscaleValidationException(
                'Invalid codice fiscale',
                CodiceFiscaleValidationException::WRONG_SIZE
            );
        }

        return true;
    }
}
