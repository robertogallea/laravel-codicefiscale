<?php

return [
    'city-decoder' => '\robertogallea\LaravelCodiceFiscale\CityCodeDecoders\InternationalCitiesStaticList',

    // The following parameters are used when using IstatRemoveCSVList city decoder class
    // The url where the CSV provided by ISTAT is served (should never change)
    'istat-csv-url' => env('CF_ISTAT_CSV_URL', 'https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.csv'),

    // Cache duration (in seconds) for storing the list downloaded from the CSV
    'cache-duration' => env('CF_CACHE_DURATION', 60 * 60 * 24),

    // When using CompositeCitiesList, you may specify the CityDecoders to merge the results from
    'cities-decoder-list' => [
        // '\robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ISTATRemoteCSVList',
        // '\robertogallea\LaravelCodiceFiscale\CityCodeDecoders\InternationalCitiesStaticList',
    ],

    // used date format for parsing
    'date-format' => 'Y-m-d',

    // used labels for parsing
    'labels' => [
        'male' => 'M',

        'female' => 'F',
    ],

];
