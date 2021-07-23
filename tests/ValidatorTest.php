<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;

class ValidatorTest extends TestCase
{
    /** @test */
    public function it_validates_good_codice_fiscale()
    {
        $rules = [
            'cf_field' => 'codice_fiscale',
        ];

        $data = [
            'cf_field' => 'RSSMRA95E05F205Z',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(true, $validator->passes());
    }

    /** @test */
    public function it_doesnt_validate_wrong_codice_fiscale()
    {
        $rules = [
            'cf_field' => 'codice_fiscale',
        ];

        $data = [
            'cf_field' => 'RSSMRA95E05F205*',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
    }

    protected function getPackageProviders($app)
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }

}