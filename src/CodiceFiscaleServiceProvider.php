<?php

namespace robertogallea\LaravelCodiceFiscale;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface;
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
            return new CodiceFiscale(
                $app->get(CityDecoderInterface::class),
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
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'codicefiscale');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/codicefiscale'),
        ], 'lang');
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
