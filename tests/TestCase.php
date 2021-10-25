<?php

namespace Tests;

use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }
}
