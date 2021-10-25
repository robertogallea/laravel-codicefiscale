<?php

namespace Tests\CityCodeDecoders;

use Tests\TestCase;

class ItalianCitiesStaticListTest extends TestCase
{
    /** @test */
    public function it_returns_an_array()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ItalianCitiesStaticList();
        $list = $cityCodeDecoder->getList();

        $this->assertIsArray($list);
    }
}
