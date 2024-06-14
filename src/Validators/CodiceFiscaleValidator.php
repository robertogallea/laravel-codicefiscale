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


    public function validate($attribute, $value, $parameters, $validator): bool
    {
        $errorCodes = [
            'first_name' => CodiceFiscaleValidationException::WRONG_FIRST_NAME,
            'last_name' => CodiceFiscaleValidationException::WRONG_LAST_NAME,
            'day' => CodiceFiscaleValidationException::WRONG_BIRTH_DAY,
            'month' => CodiceFiscaleValidationException::WRONG_BIRTH_MONTH,
            'year' => CodiceFiscaleValidationException::WRONG_BIRTH_YEAR,
            'place' => CodiceFiscaleValidationException::WRONG_BIRTH_PLACE,
            'gender' => CodiceFiscaleValidationException::WRONG_GENDER,
        ];

        try {
            $this->codiceFiscale->parse($value);
            $data = $validator->getData();

            if (count($parameters)) {
                $pieces = [
                    'first_name' => '',
                    'last_name' => '',
                    'birthdate' => '',
                    'place' => '',
                    'gender' => '',
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
                } catch (\Exception $exception) {
                    throw new CodiceFiscaleValidationException(
                        'Invalid codice fiscale',
                        CodiceFiscaleValidationException::EMPTY_BIRTHDATE
                    );
                }

                if ($value != $cf) {
                    $newCodiceFiscale = new CodiceFiscale();
                    $newCodiceFiscale->parse($cf);
                    $this->compareAttribute('First Name', $newCodiceFiscale->getFirstName(), $this->codiceFiscale->getFirstName(), $errorCodes['first_name']);
                    $this->compareAttribute('Last Name', $newCodiceFiscale->getLastName(), $this->codiceFiscale->getLastName(), $errorCodes['last_name']);
                    $this->compareAttribute('Day', $newCodiceFiscale->getDay(), $this->codiceFiscale->getDay(), $errorCodes['day']);
                    $this->compareAttribute('Month', $newCodiceFiscale->getMonth(), $this->codiceFiscale->getMonth(), $errorCodes['month']);
                    $this->compareAttribute('Year', $newCodiceFiscale->getYear(), $this->codiceFiscale->getYear(), $errorCodes['year']);
                    $this->compareAttribute('Birth Place', $newCodiceFiscale->getBirthPlace(), $this->codiceFiscale->getBirthPlace(), $errorCodes['place']);
                    $this->compareAttribute('Gender', $newCodiceFiscale->getGender(), $this->codiceFiscale->getGender(), $errorCodes['gender']);
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
                case CodiceFiscaleValidationException::EMPTY_BIRTHDATE:
                    $error_msg = trans('codicefiscale::validation.empty_birthdate', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_FIRST_NAME:
                    $error_msg = trans('codicefiscale::validation.wrong_first_name', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_LAST_NAME:
                    $error_msg = trans('codicefiscale::validation.wrong_last_name', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_BIRTH_DAY:
                    $error_msg = trans('codicefiscale::validation.wrong_birth_day', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_BIRTH_MONTH:
                    $error_msg = trans('codicefiscale::validation.wrong_birth_month', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_BIRTH_YEAR:
                    $error_msg = trans('codicefiscale::validation.wrong_birth_year', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_BIRTH_PLACE:
                    $error_msg = trans('codicefiscale::validation.wrong_birth_place', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
                    break;
                case CodiceFiscaleValidationException::WRONG_GENDER:
                    $error_msg = trans('codicefiscale::validation.wrong_gender', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
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

    public function compareAttribute($attribute, $new, $old, $error): void
    {
        if ($new != $old) {
            throw new CodiceFiscaleValidationException(
                "Invalid $attribute",
                $error
            );
        }
    }
}
