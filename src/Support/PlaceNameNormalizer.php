<?php

namespace Robertogallea\CodiceFiscale\Support;

/**
 * Normalizes a birthplace name for matching (case, accents, stray
 * punctuation, extra whitespace) while preserving word and apostrophe
 * boundaries - unlike ItalianNameNormalizer, which strips those too
 * because it targets CF encoding rather than name search. Losing
 * boundaries here would make e.g. "L'Aquila" indistinguishable from
 * a hypothetical "Laquila".
 */
final class PlaceNameNormalizer
{
    public function normalize(string $name): string
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');
        $transliterated = strtr($upper, LatinDiacritics::MAP);
        $filtered = preg_replace("/[^A-Z' ]/", '', $transliterated) ?? '';
        $collapsed = preg_replace('/\s+/', ' ', $filtered) ?? '';

        return trim($collapsed);
    }
}
