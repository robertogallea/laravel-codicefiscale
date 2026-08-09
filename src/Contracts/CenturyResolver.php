<?php

namespace Robertogallea\CodiceFiscale\Contracts;

interface CenturyResolver
{
    /**
     * @param  array{int, int}  $possibleYears  exactly two 4-digit
     *                                          candidate years 100 years apart, e.g. [1926, 2026]
     */
    public function resolve(array $possibleYears): int;
}
