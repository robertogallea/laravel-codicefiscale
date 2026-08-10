<?php

namespace Robertogallea\CodiceFiscale\Generation;

use Robertogallea\CodiceFiscale\Contracts\NameNormalizer;
use Robertogallea\CodiceFiscale\Support\LatinDiacritics;

/**
 * Normalizes a name as a person actually typed it (accents,
 * apostrophes, mixed case, extra whitespace) into the plain
 * uppercase A-Z form the encoding algorithm expects.
 */
final class ItalianNameNormalizer implements NameNormalizer
{
    public function normalize(string $name): string
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');
        $transliterated = strtr($upper, LatinDiacritics::MAP);

        return preg_replace('/[^A-Z]/', '', $transliterated) ?? '';
    }
}
