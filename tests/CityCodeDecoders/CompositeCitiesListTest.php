<?php

namespace Tests\CityCodeDecoders;

use Illuminate\Support\Facades\Config;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CompositeCitiesList;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;
use Tests\TestCase;

class CompositeCitiesListTest extends TestCase
{
    /** @test */
    public function if_empty_returns_empty_array()
    {
        Config::set('codicefiscale.cities-lists', []);
        $citiesList = new CompositeCitiesList();

        $list = $citiesList->getList();

        $this->assertEquals([], $list);
    }

    /** @test */
    public function it_merges_two_cities_list_results()
    {
        $citiesList = $this->getMockedComposedList(['A001' => 'AAA'], ['B001' => 'BBB']);

        $list = $citiesList->getList();

        $this->assertEquals([
            'A001' => 'AAA',
            'B001' => 'BBB',
        ], $list);
    }

    /** @test */
    public function when_merging_last_decoder_has_precedence()
    {
        $citiesList = $this->getMockedComposedList(['A001' => 'AAA'], ['A001' => 'BBB']);

        $list = $citiesList->getList();

        $this->assertEquals([
            'A001' => 'BBB',
        ], $list);
    }

    /**
     * @return array|mixed
     */
    private function getMockedComposedList($firstArray, $secondArray)
    {
        $list1 = \Mockery::namedMock(
            'FirstDecoder',
            CityDecoderInterface::class,
            function ($mock) use ($firstArray) {
                $mock->shouldReceive('getList')->once()->andReturn($firstArray);
            }
        );

        $list2 = \Mockery::namedMock(
            'SecondDecoder',
            CityDecoderInterface::class,
            function ($mock) use ($secondArray) {
                $mock->shouldReceive('getList')->once()->andReturn($secondArray);
            }
        );

        Config::set('codicefiscale.cities-decoder-list', $citiesListDecoders = [
            get_class($list1),
            get_class($list2),
        ]);

        $citiesList = new CompositeCitiesList();

        return $citiesList;
    }

    public function getPackageProviders($application)
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }
}
