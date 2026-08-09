<?php

namespace Robertogallea\CodiceFiscale\Laravel;

use Illuminate\Support\ServiceProvider;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\CompositeBirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\EloquentBirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

class CodiceFiscaleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/codicefiscale.php'), 'codicefiscale');

        $this->app->bind(BirthPlaceRepository::class, function () {
            return new CompositeBirthPlaceRepository(
                new EloquentBirthPlaceRepository(Municipality::class),
                new EloquentBirthPlaceRepository(ForeignCountry::class),
            );
        });
    }

    public function boot(): void
    {
        // Deliberately not in register(): the database connection
        // must reflect final config state (host app config, published
        // config overrides, or a test's environment overrides), and
        // register() runs too early for that - before those overrides
        // are applied. Container bindings above stay in register()
        // since they're resolved lazily, well after boot() completes.
        $this->registerDatabaseConnection();

        $this->loadMigrationsFrom($this->packagePath('database/migrations'));

        $this->publishes([
            $this->packagePath('config/codicefiscale.php') => config_path('codicefiscale.php'),
        ], 'config');
    }

    private function registerDatabaseConnection(): void
    {
        /** @var string $path */
        $path = config('codicefiscale.database.path');

        if ($path !== ':memory:' && ! file_exists($path)) {
            @mkdir(dirname($path), 0755, true);
            touch($path);
        }

        config([
            'database.connections.codicefiscale' => [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
    }

    private function packagePath(string $path): string
    {
        return __DIR__."/../../$path";
    }
}
