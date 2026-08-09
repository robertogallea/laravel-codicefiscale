<?php

namespace Robertogallea\CodiceFiscale\Contracts;

interface NameNormalizer
{
    public function normalize(string $name): string;
}
