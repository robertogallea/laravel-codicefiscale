<?php

namespace robertogallea\LaravelCodiceFiscale\Checks;

use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

interface Check
{
    /**
     * @throws CodiceFiscaleValidationException
     */
    public function check($code): bool;
}
