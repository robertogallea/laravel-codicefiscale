<?php

namespace Robertogallea\CodiceFiscale\Generation;

use Robertogallea\CodiceFiscale\Contracts\NameNormalizer;

/**
 * Normalizes a name as a person actually typed it (accents,
 * apostrophes, mixed case, extra whitespace) into the plain
 * uppercase A-Z form the encoding algorithm expects. Handles Latin-
 * script diacritics; broader multi-script transliteration is out of
 * scope for this implementation.
 */
final class ItalianNameNormalizer implements NameNormalizer
{
    private const TRANSLITERATION = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N', 'Ý' => 'Y',
    ];

    public function normalize(string $name): string
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');
        $transliterated = strtr($upper, self::TRANSLITERATION);

        return preg_replace('/[^A-Z]/', '', $transliterated) ?? '';
    }
}
