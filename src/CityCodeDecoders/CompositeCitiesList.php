<?php

namespace robertogallea\LaravelCodiceFiscale\CityCodeDecoders;

class CompositeCitiesList implements CityDecoderInterface
{
    public static function getList()
    {
        $result = [];

        foreach (config('codicefiscale.cities-decoder-list') as $citiesList) {
            $list = $citiesList::getList();
            $result = $list + $result;
        }

        return $result;
    }
}
