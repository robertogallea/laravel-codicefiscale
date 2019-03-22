<?php

namespace robertogallea\LaravelCodiceFiscale;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class CodiceFiscaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        Validator::extend('codice_fiscale', function ($attribute, $value, $parameters, $validator) {
            $cf = new CodiceFiscale();
            $result = $cf->parse($value);



            if(!$result){
                $error_msg = null;
                switch ($cf->getError()) {
                    case CodiceFiscale::NO_CODE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans("validation.codice_fiscale.no_code"));
                        break;
                    case CodiceFiscale::WRONG_SIZE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans("validation.codice_fiscale.wrong_size"));
                        break;
                    case CodiceFiscale::BAD_CHARACTERS:
                        $error_msg = str_replace([':attribute'], [$attribute], trans("validation.codice_fiscale.wrong_size"));
                        break;
                    case CodiceFiscale::BAD_OMOCODIA_CHAR:
                        $error_msg = str_replace([':attribute'], [$attribute], trans("validation.codice_fiscale.wrong_size"));
                        break;
                    case CodiceFiscale::WRONG_CODE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans("validation.codice_fiscale.wrong_code"));
                        break;
                }
                $validator->addReplacer('codice_fiscale',  function ($message, $attribute, $rule, $parameters) use ($error_msg) {
                    return str_replace([':attribute'], [$attribute], str_replace('codice fiscale', ':attribute', $error_msg));
                });

                return false;
            }

            return true;
        });


    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
