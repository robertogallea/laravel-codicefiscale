<?php

namespace robertogallea\LaravelCodiceFiscale;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface;
use robertogallea\LaravelCodiceFiscale\Faker\CodiceFiscaleFakerProvider;
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
        $this->config();

        $this->translations();

        $this->app->bind(
            CityDecoderInterface::class,
            config('codicefiscale.city-decoder')
        );

        $this->app->singleton(Generator::class, function () {
            $faker = Factory::create();
            $faker->addProvider(new CodiceFiscaleFakerProvider($faker));
            return $faker;
        });
    }

    public function bootValidator()
    {
        Validator::extend('codice_fiscale', CodiceFiscaleValidator::class);
    }

    private function config()
    {
        $configPath = $this->packagePath('config/codicefiscale.php');

        $this->publishes([
            $configPath => config_path('codicefiscale.php'),
        ], 'config');

        $this->mergeConfigFrom($configPath, 'codicefiscale');
    }

    private function translations()
    {
        $translationsPath = $this->packagePath('lang');

        $this->loadTranslationsFrom($translationsPath, 'codicefiscale');

        $this->publishes([
            $translationsPath => $this->app->langPath('vendor/codicefiscale'),
        ], 'lang');
    }

    private function packagePath($path)
    {
        return __DIR__."/../$path";
    }
}
