<?php

namespace robertogallea\LaravelCodiceFiscale;

class CodiceFiscaleConfig
{
    protected string $dateFormat;

    protected string $maleLabel;

    protected string $femaleLabel;

    public function __construct()
    {
        $this->dateFormat = config('codicefiscale.date-format');
        $this->maleLabel = config('codicefiscale.labels.male');
        $this->femaleLabel = config('codicefiscale.labels.female');
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function getMaleLabel(): ?string
    {
        return $this->maleLabel;
    }

    public function getFemaleLabel(): ?string
    {
        return $this->femaleLabel;
    }
}
