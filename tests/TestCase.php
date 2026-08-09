<?php

namespace Tests;

use Robertogallea\CodiceFiscale\Laravel\CodiceFiscaleServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Ephemeral, per-test database - never a real file on disk.
        $app['config']->set('codicefiscale.database.path', ':memory:');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'codicefiscale'])->run();
    }
}
