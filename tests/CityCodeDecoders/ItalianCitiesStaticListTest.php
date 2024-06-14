<?php

namespace Tests\CityCodeDecoders;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ItalianCitiesStaticListTest extends TestCase
{
    #[Test]
    public function it_returns_an_array()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ItalianCitiesStaticList();
        $list = $cityCodeDecoder->getList();

        $this->assertIsArray($list);
    }
}
