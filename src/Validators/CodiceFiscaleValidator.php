<?php

namespace robertogallea\LaravelCodiceFiscale\Validators;

use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleGenerationException;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CodiceFiscaleValidator
{
    protected CodiceFiscale $codiceFiscale;

    public function __construct(CodiceFiscale $codiceFiscale)
    {
        $this->codiceFiscale = $codiceFiscale;
    }

    public function validate($attribute, $value, $parameters, $validator)
    {
        try {
            $this->codiceFiscale->parse($value);

            $data = $validator->getData();

            if (count($parameters)) {
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
                    $error_msg = trans('codicefiscale::validation.no_code', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_SIZE:
                    $error_msg = trans('codicefiscale::validation.wrong_size', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::BAD_CHARACTERS:
                    $error_msg = trans('codicefiscale::validation.bad_characters', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::BAD_OMOCODIA_CHAR:
                    $error_msg = trans('codicefiscale::validation.bad_omocodia_char', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_CODE:
                    $error_msg = trans('codicefiscale::validation.wrong_code', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::MISSING_CITY_CODE:
                    $error_msg = trans('codicefiscale::validation.missing_city_code', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::NO_MATCH:
                    $error_msg = trans('codicefiscale::validation.no_match', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                default:
                    $error_msg = trans('codicefiscale::validation.wrong_code', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
            }

            $validator->addReplacer('codice_fiscale', function ($message, $attribute, $rule, $parameters, $validator) use ($error_msg) {
                return str_replace([':attribute'], [$validator->getDisplayableAttribute($attribute)], str_replace('codice fiscale', ':attribute', $error_msg));
            });

            return false;
        }

        return true;
    }
}
