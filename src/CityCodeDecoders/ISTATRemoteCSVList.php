<?php

namespace robertogallea\LaravelCodiceFiscale\CityCodeDecoders;

use GuzzleHttp\Client;

class ISTATRemoteCSVList implements CityDecoderInterface
{
    protected const accentTable = [
        'Š' => 'S\'', 'š' => 's\'', 'Ž' => 'Z\'', 'ž' => 'z\'', 'À' => 'A\'', 'Á' => 'A\'', 'Â' => 'A\'', 'Ã' => 'A\'', 'Ä' => 'A\'', 'Å' => 'A\'', 'Æ' => 'A\'', 'Ç' => 'C\'', 'È' => 'E\'', 'É' => 'E\'',
        'Ê' => 'E\'', 'Ë' => 'E\'', 'Ì' => 'I\'', 'Í' => 'I\'', 'Î' => 'I\'', 'Ï' => 'I\'', 'Ñ' => 'N\'', 'Ò' => 'O\'', 'Ó' => 'O\'', 'Ô' => 'O\'', 'Õ' => 'O\'', 'Ö' => 'O\'', 'Ø' => 'O\'', 'Ù' => 'U\'',
        'Ú' => 'U\'', 'Û' => 'U\'', 'Ü' => 'U\'', 'Ý' => 'Y\'', 'Þ' => 'B\'', 'ß' => 'Ss\'', 'à' => 'a\'', 'á' => 'a\'', 'â' => 'a\'', 'ã' => 'a\'', 'ä' => 'a\'', 'å' => 'a\'', 'æ' => 'a\'', 'ç' => 'c\'',
        'è' => 'e\'', 'é' => 'e\'', 'ê' => 'e\'', 'ë' => 'e\'', 'ì' => 'i\'', 'í' => 'i\'', 'î' => 'i\'', 'ï' => 'i\'', 'ð' => 'o\'', 'ñ' => 'n\'', 'ò' => 'o\'', 'ó' => 'o\'', 'ô' => 'o\'', 'õ' => 'o\'',
        'ö' => 'o\'', 'ø' => 'o\'', 'ù' => 'u\'', 'ú' => 'u\'', 'û' => 'u\'', 'ý' => 'y\'', 'þ' => 'b\'', 'ÿ' => 'y',
    ];

    public static function getList()
    {
        return \Cache::remember('cities-list', config('codicefiscale.cache-duration'), function () {
            $client = new Client();
            $response = $client->request('GET', config('codicefiscale.istat-csv-url'));

            $body = iconv('ISO-8859-1', 'UTF-8', $response->getBody());

            $body = self::str_replace_times("\n", '', $body, 2);

            $data = self::getCsv($body);
            $list = self::csvToList($data);

            return $list;
        });
    }

    private static function str_replace_times($from, $to, $content, $times)
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $content, $times);
    }

    private static function getCsv(string $body)
    {
        $data = str_getcsv($body, "\n");

        foreach ($data as &$row) {
            $row = str_getcsv($row, ';');
        }

        return $data;
    }

    private static function csvToList(array $data)
    {
        unset($data[0]);
        $newData = [];

        $data = array_walk($data, function ($row) use (&$newData) {
            $newData[$row[19]] = strtoupper(strtr($row[6], self::accentTable));
        });

        return $newData;
    }

    public function flushCache()
    {
        cache()->forget('cities-list');
    }
}
