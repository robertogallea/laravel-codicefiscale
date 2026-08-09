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
        // The service provider migrates it automatically in boot(),
        // via its own isolated Migrator - no manual artisan call needed.
        $app['config']->set('codicefiscale.database.path', ':memory:');
    }
}
