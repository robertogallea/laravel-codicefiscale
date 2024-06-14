<?php

namespace Tests\CityCodeDecoders;

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;
use Tests\TestCase;

class IstatRemoteCSVListTest extends TestCase
{
    #[Test]
    public function it_returns_an_array()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ISTATRemoteCSVList();
        $list = $cityCodeDecoder->getList();

        $this->assertIsArray($list);
    }

    #[Test]
    public function it_loads_data_from_istat_remove_csv()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ISTATRemoteCSVList();
        $list = $cityCodeDecoder->getList();

        $this->assertArrayHasKey('A001', $list);
        $this->assertEquals($list['A001'], 'ABANO TERME');
    }

    protected function getPackageProviders($app): array
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }

    #[Test]
    public function it_uses_cache_for_successive_calls()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ISTATRemoteCSVList();
        $cityCodeDecoder->flushCache();

        $list = $cityCodeDecoder->getList();

        Cache::shouldReceive('remember')
            ->once()
            ->with('cities-list', \Mockery::any(), \Mockery::any())
            ->andReturn($list);

        $list2 = $cityCodeDecoder->getList();

        $this->assertEquals($list, $list2);
    }
}
