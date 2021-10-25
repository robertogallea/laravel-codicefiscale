<?php

namespace robertogallea\LaravelCodiceFiscale;

class CodiceFiscaleConfig
{
    protected $dateFormat;
    protected $maleLabel;
    protected $femaleLabel;

    public function __construct()
    {
        $this->dateFormat = config('codicefiscale.date-format');
        $this->maleLabel = config('codicefiscale.labels.male');
        $this->femaleLabel = config('codicefiscale.labels.female');
    }

    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    public function getMaleLabel()
    {
        return $this->maleLabel;
    }

    public function getFemaleLabel()
    {
        return $this->femaleLabel;
    }
}