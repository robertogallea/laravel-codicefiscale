<?php

namespace robertogallea\LaravelCodiceFiscale;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleGenerationException;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CodiceFiscaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(CodiceFiscale $codiceFiscale)
    {
        $this->bootValidator($codiceFiscale);
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

    public function bootValidator(CodiceFiscale $codiceFiscale)
    {
        Validator::extend('codice_fiscale', function ($attribute, $value, $parameters, $validator) use ($codiceFiscale) {
            try {
                $codiceFiscale->parse($value);

                $data = $validator->getData();

                if (sizeof($parameters)) {
                    $pieces = [
                        'first_name' => '',
                        'last_name'  => '',
                        'birthdate'  => '',
                        'place'      => '',
                        'gender'     => '',
                    ];

                    foreach ($parameters as $parameter) {
                        $pair = explode('=', $parameter);
                        $pieces[$pair[0]] = $data[$pair[1]] ?? '';
                    }

                    try {
                        $cf = CodiceFiscale::generate(...array_values($pieces));
                    } catch (CodiceFiscaleGenerationException $exception) {
                        throw new CodiceFiscaleValidationException(
                            'Invalid codice fiscale',
                            CodiceFiscaleValidationException::NO_MATCH
                        );
                    }
                    if ($value != $cf) {
                        throw new CodiceFiscaleValidationException(
                            'Invalid codice fiscale',
                            CodiceFiscaleValidationException::NO_MATCH
                        );
                    }
                }
            } catch (CodiceFiscaleValidationException $exception) {
                switch ($exception->getCode()) {
                    case CodiceFiscaleValidationException::NO_CODE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.no_code'));
                        break;
                    case CodiceFiscaleValidationException::WRONG_SIZE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.wrong_size'));
                        break;
                    case CodiceFiscaleValidationException::BAD_CHARACTERS:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.bad_characters'));
                        break;
                    case CodiceFiscaleValidationException::BAD_OMOCODIA_CHAR:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.bad_omocodia_char'));
                        break;
                    case CodiceFiscaleValidationException::WRONG_CODE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.wrong_code'));
                        break;
                    case CodiceFiscaleValidationException::MISSING_CITY_CODE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.missing_city_code'));
                        break;
                    case CodiceFiscaleValidationException::NO_MATCH:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.no_match'));
                        break;
                    default:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.wrong_code'));
                }

                $validator->addReplacer('codice_fiscale', function ($message, $attribute, $rule, $parameters, $validator) use ($error_msg) {
                    return str_replace([':attribute'], [$validator->getDisplayableAttribute($attribute)], str_replace('codice fiscale', ':attribute', $error_msg));
                });

                return false;
            }

            return true;
        });
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
