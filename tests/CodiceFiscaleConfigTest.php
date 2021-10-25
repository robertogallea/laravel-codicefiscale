<?php

namespace Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleConfig;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;

class CodiceFiscaleConfigTest extends TestCase
{
    public function testLoadsDefault()
    {
        $config = resolve(CodiceFiscaleConfig::class);

        $this->assertEquals('Y-m-d', $config->getDateFormat());
        $this->assertEquals('M', $config->getMaleLabel());
        $this->assertEquals('F', $config->getFemaleLabel());
    }

    /**
     * @test
     * @dataProvider configChange
     */
    public function changing_config_params_does_not_affect_parsing($cf, $configKey, $configValue, $cfValue, $cfPart)
    {
        Config::set($configKey, $configValue);

        $codice_fiscale = $cf;
        $cf = new CodiceFiscale();

        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res[$cfPart], $cfValue);
    }

    public function configChange()
    {
        return [
            ['RSSMRA95E05F205Z', 'codicefiscale.labels.male', 'male', 'male', 'gender'],
            ['RSSMRA95E45F205D', 'codicefiscale.labels.female', 'female', 'female', 'gender'],
            ['RSSMRA95E05F205Z', 'codicefiscale.date-format', 'd/m/Y', Carbon::parse('1995-05-05'), 'birthdate'],
        ];
    }

}
