<?php

namespace Robertogallea\CodiceFiscale;

use Robertogallea\CodiceFiscale\Exceptions\InvalidCodiceFiscaleException;

final class CodiceFiscale implements \Stringable
{
    /**
     * Structural shape of a codice fiscale: 3 surname-code letters, 3
     * name-code letters, 2 year digits (or omocodia letters), 1 month
     * letter, 2 day digits (or omocodia letters), 1 birthplace-code
     * prefix letter, 3 birthplace-code digits (or omocodia letters),
     * 1 check-character letter. Omocodia substitution only ever
     * replaces a digit with one of L,M,N,P,Q,R,S,T,U,V. The month
     * letter is restricted to A,B,C,D,E,H,L,M,P,R,S,T (January
     * through December, in that order) — no other letter is valid
     * in that position.
     */
    private const STRUCTURE_PATTERN =
        '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/';

    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function from(string $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidCodiceFiscaleException(
            sprintf('"%s" is not a structurally valid codice fiscale.', $value)
        );
    }

    public static function tryFrom(string $value): ?self
    {
        $normalized = strtoupper(trim($value));

        if (preg_match(self::STRUCTURE_PATTERN, $normalized) !== 1) {
            return null;
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
