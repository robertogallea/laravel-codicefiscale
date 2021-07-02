<?php


namespace Tests\CityCodeDecoders;


use Orchestra\Testbench\TestCase;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;

class IstatRemoteCSVListTest extends TestCase
{
    /** @test */
    public function it_returns_an_array()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ISTATRemoteCSVList();
        $list = $cityCodeDecoder->getList();

        $this->assertIsArray($list);
    }

    /** @test */
    public function it_loads_data_from_istat_remove_csv()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ISTATRemoteCSVList();
        $list = $cityCodeDecoder->getList();

        $this->assertArrayHasKey('A001', $list);
        $this->assertEquals($list['A001'], 'ABANO TERME');
    }

    protected function getPackageProviders($app)
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }

    /** @test */
    public function it_uses_cache_for_successive_calls()
    {
        $cityCodeDecoder = new \robertogallea\LaravelCodiceFiscale\CityCodeDecoders\ISTATRemoteCSVList();
        $cityCodeDecoder->flushCache();

        $list = $cityCodeDecoder->getList();

        \Cache::shouldReceive('remember')
            ->once()
            ->with('cities-list', \Mockery::any(), \Mockery::any())
            ->andReturn($list);

        $list2 = $cityCodeDecoder->getList();

        $this->assertEquals($list, $list2);
    }


}