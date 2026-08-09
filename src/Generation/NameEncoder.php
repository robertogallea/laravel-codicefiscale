<?php

namespace Robertogallea\CodiceFiscale\Generation;

/**
 * Encodes an already-normalized (uppercase, A-Z only) surname or
 * first name into its 3-letter code. Expects normalize() to have
 * already run - see NameNormalizer.
 */
final class NameEncoder
{
    private const CONSONANTS = [
        'B', 'C', 'D', 'F', 'G', 'H', 'J', 'K',
        'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T',
        'V', 'W', 'X', 'Y', 'Z',
    ];

    private const VOWELS = ['A', 'E', 'I', 'O', 'U'];

    public function surnameCode(string $normalizedSurname): string
    {
        $consonants = $this->consonantsOf($normalizedSurname);

        $code = implode('', array_slice($consonants, 0, 3));

        return $this->padWithVowelsThenX($code, $normalizedSurname);
    }

    public function nameCode(string $normalizedName): string
    {
        $consonants = $this->consonantsOf($normalizedName);

        if (count($consonants) <= 3) {
            $code = implode('', $consonants);
        } else {
            // First name special case: skip the 2nd consonant when
            // there are 4 or more, taking the 1st, 3rd and 4th instead.
            $code = $consonants[0].$consonants[2].$consonants[3];
        }

        return $this->padWithVowelsThenX($code, $normalizedName);
    }

    /** @return list<string> */
    private function consonantsOf(string $normalized): array
    {
        return $this->lettersMatching($normalized, self::CONSONANTS);
    }

    /** @return list<string> */
    private function vowelsOf(string $normalized): array
    {
        return $this->lettersMatching($normalized, self::VOWELS);
    }

    /**
     * @param  list<string>  $allowedLetters
     * @return list<string>
     */
    private function lettersMatching(string $normalized, array $allowedLetters): array
    {
        return array_values(array_filter(
            str_split($normalized),
            static fn (string $letter): bool => in_array($letter, $allowedLetters, true)
        ));
    }

    private function padWithVowelsThenX(string $code, string $normalized): string
    {
        if (strlen($code) < 3) {
            foreach ($this->vowelsOf($normalized) as $vowel) {
                if (strlen($code) >= 3) {
                    break;
                }
                $code .= $vowel;
            }
        }

        return str_pad($code, 3, 'X');
    }
}
