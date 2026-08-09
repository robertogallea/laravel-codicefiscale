<?php

namespace Robertogallea\CodiceFiscale\Contracts;

/**
 * Normalizes a name as a person actually typed it (accents,
 * apostrophes, mixed case, extra whitespace) into the plain
 * uppercase form the encoding algorithm expects.
 */
interface NameNormalizer
{
    public function normalize(string $name): string;
}
