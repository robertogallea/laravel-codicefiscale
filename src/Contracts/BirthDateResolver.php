<?php

namespace Robertogallea\CodiceFiscale\Contracts;

use Robertogallea\CodiceFiscale\Data\BirthDateResolutionContext;

interface BirthDateResolver
{
    /**
     * The most plausible complete birth date given the context's
     * candidates, or null when none is plausible - a codice fiscale's
     * two-digit year does not always contain enough information to
     * resolve a complete date.
     */
    public function resolve(BirthDateResolutionContext $context): ?\DateTimeImmutable;
}
