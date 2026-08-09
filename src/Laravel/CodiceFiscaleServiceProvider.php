<?php

namespace Robertogallea\CodiceFiscale\Laravel;

use Faker\Generator as FakerGenerator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\CompositeBirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\EloquentBirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;
use Robertogallea\CodiceFiscale\Laravel\Commands\UpdatePlacesCommand;
use Robertogallea\CodiceFiscale\Laravel\Faker\CodiceFiscaleProvider;
use Robertogallea\CodiceFiscale\Validation\Validator as CodiceFiscaleValidator;

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
        $this->migrate();
        $this->registerValidationRule();
        $this->registerFakerProvider();

        $this->publishes([
            $this->packagePath('config/codicefiscale.php') => config_path('codicefiscale.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdatePlacesCommand::class,
            ]);
        }
    }

    private function registerDatabaseConnection(): void
    {
        /** @var string $path */
        $path = config('codicefiscale.database.path');

        if ($path !== ':memory:' && ! file_exists($path)) {
            if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) {
                throw new \RuntimeException("Unable to create directory for the codicefiscale SQLite database at \"$path\".");
            }

            if (touch($path) === false) {
                throw new \RuntimeException("Unable to create the codicefiscale SQLite database file at \"$path\".");
            }
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

    /**
     * Deliberately NOT loadMigrationsFrom(): that registers migrations
     * into the host application's own shared "migrate" command, whose
     * bookkeeping repository connection is governed by that command's
     * --database option (or config('database.default') if omitted) -
     * NOT by each migration's own $connection property. A bare
     * `php artisan migrate` in a host app would write tracking rows
     * for our migrations into the *host's own default connection's*
     * migrations table, even though the schema itself correctly lands
     * on ours. Running our own isolated Migrator/repository instance
     * here never touches the host's migration command or its
     * repository singleton at all.
     *
     * Migrator::setConnection() has its own surprising side effect:
     * it also calls the connection resolver's setDefaultConnection(),
     * globally changing config('database.default') for the rest of
     * the request/process. Saving and restoring it around the run
     * keeps that mutation from ever escaping this method.
     */
    private function migrate(): void
    {
        $db = $this->app->make(DatabaseManager::class);
        $originalDefaultConnection = $db->getDefaultConnection();

        $repository = new DatabaseMigrationRepository($db, 'migrations');
        $repository->setSource('codicefiscale');

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $migrator = new Migrator(
            $repository,
            $db,
            $this->app->make(Filesystem::class),
            $this->app->make(Dispatcher::class),
        );

        try {
            $migrator->setConnection('codicefiscale');
            $migrator->run([$this->packagePath('database/migrations')]);
        } finally {
            $db->setDefaultConnection($originalDefaultConnection);
        }
    }

    /**
     * The 2.x-retained convenience alias: format/checksum/semantics
     * only, exactly what CodiceFiscaleRule::make() alone does, for
     * callers who just need a plain pipe-delimited rule string.
     */
    private function registerValidationRule(): void
    {
        $this->app->make(ValidationFactory::class)->extend(
            'codice_fiscale',
            fn ($attribute, $value) => is_string($value)
                && $this->app->make(CodiceFiscaleValidator::class)->validate($value)->valid(),
            'The :attribute is not a valid codice fiscale.',
        );
    }

    /**
     * fakerphp/faker is usually a dev-only dependency of the consuming
     * application (sometimes absent entirely in production), so this
     * integration is entirely optional rather than a hard runtime
     * requirement - guarded by class_exists(), and wired via
     * afterResolving() so it reaches every per-locale Faker\Generator
     * instance Laravel's own DatabaseServiceProvider lazily creates,
     * not just whichever one happens to exist at boot() time.
     */
    private function registerFakerProvider(): void
    {
        if (! class_exists(FakerGenerator::class)) {
            return;
        }

        $this->app->afterResolving(FakerGenerator::class, function (FakerGenerator $faker): void {
            $faker->addProvider(new CodiceFiscaleProvider($faker));
        });
    }

    private function packagePath(string $path): string
    {
        return __DIR__."/../../$path";
    }
}
