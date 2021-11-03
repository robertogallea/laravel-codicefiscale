<?php

namespace robertogallea\LaravelCodiceFiscale;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleGenerationException;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;
use robertogallea\LaravelCodiceFiscale\Validators\CodiceFiscaleValidator;

class CodiceFiscaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootValidator();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->publishConfig();

        $this->app->singleton(CodiceFiscale::class, function ($app) {
            $decoder = config('codicefiscale.city-decoder');

            return new CodiceFiscale(
                new $decoder(),
                $app->get(CodiceFiscaleConfig::class)
            );
        });

        $this->app->bind(
            CityDecoderInterface::class,
            config('codicefiscale.city-decoder')
        );
    }

    public function bootValidator()
    {
        Validator::extend('codice_fiscale', CodiceFiscaleValidator::class);
    }

    private function publishConfig()
    {
        $configPath = $this->packagePath('config/codicefiscale.php');

        $this->publishes([
            $configPath => config_path('codicefiscale.php'),
        ], 'config');

        $this->mergeConfigFrom($configPath, 'codicefiscale');
    }

    private function packagePath($path)
    {
        return __DIR__."/../$path";
    }
}