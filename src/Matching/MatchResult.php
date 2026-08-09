<?php

namespace Robertogallea\CodiceFiscale\Matching;

use Robertogallea\CodiceFiscale\Enums\PersonField;

final readonly class MatchResult
{
    /**
     * @param  list<PersonField>  $matched
     * @param  list<PersonField>  $mismatched
     * @param  list<PersonField>  $skipped
     */
    public function __construct(
        private array $matched,
        private array $mismatched,
        private array $skipped,
    ) {
    }

    /**
     * True when nothing checked failed - a fully verified match if
     * skipped() is also empty, or a partial match (nothing checked
     * failed, but some fields weren't provided) otherwise. False
     * means an explicit mismatch.
     */
    public function matches(): bool
    {
        return $this->mismatched === [];
    }

    /** @return list<PersonField> */
    public function matched(): array
    {
        return $this->matched;
    }

    /** @return list<PersonField> */
    public function mismatched(): array
    {
        return $this->mismatched;
    }

    /** @return list<PersonField> */
    public function skipped(): array
    {
        return $this->skipped;
    }
}
