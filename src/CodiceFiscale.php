<?php

namespace robertogallea\LaravelCodiceFiscale;

use Carbon\Carbon;
use robertogallea\LaravelCodiceFiscale\Checks\CheckForBadChars;
use robertogallea\LaravelCodiceFiscale\Checks\CheckForEmptyCode;
use robertogallea\LaravelCodiceFiscale\Checks\CheckForOmocodiaChars;
use robertogallea\LaravelCodiceFiscale\Checks\CheckForWrongCode;
use robertogallea\LaravelCodiceFiscale\Checks\CheckForWrongSize;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface;
use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\InternationalCitiesStaticList;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CodiceFiscale
{
    protected $config;

    protected $cityDecoder;

    private $cf = null;

    private $isValid = null;

    private $gender = null;

    private $birthPlace = null;

    private $day = null;

    private $month = null;

    private $year = null;

    private $error = null;

    private $tabDecodeOmocodia = null;

    private $tabReplacementOmocodia = null;

    private $tabDecodeMonths = null;

    private $checks = [
        CheckForEmptyCode::class,
        CheckForWrongSize::class,
        CheckForBadChars::class,
        CheckForOmocodiaChars::class,
        CheckForWrongCode::class,
    ];

    public function __construct(CityDecoderInterface $cityDecoder = null, CodiceFiscaleConfig $config = null)
    {
        $this->config = $config ?? resolve(CodiceFiscaleConfig::class);

        $defaultDecoder = config('codicefiscale.city-decoder');
        $this->cityDecoder = $cityDecoder ?? new $defaultDecoder();

        $this->tabReplacementOmocodia = [6, 7, 9, 10, 12, 13, 14];

        $this->tabDecodeOmocodia = [
            'A' => '!',
            'B' => '!',
            'C' => '!',
            'D' => '!',
            'E' => '!',
            'F' => '!',
            'G' => '!',
            'H' => '!',
            'I' => '!',
            'J' => '!',
            'K' => '!',
            'L' => '0',
            'M' => '1',
            'N' => '2',
            'O' => '!',
            'P' => '3',
            'Q' => '4',
            'R' => '5',
            'S' => '6',
            'T' => '7',
            'U' => '8',
            'V' => '9',
            'W' => '!',
            'X' => '!',
            'Y' => '!',
            'Z' => '!',
        ];

        $this->tabDecodeMonths = [
            'A' => '01',
            'B' => '02',
            'C' => '03',
            'D' => '04',
            'E' => '05',
            'H' => '06',
            'L' => '07',
            'M' => '08',
            'P' => '09',
            'R' => '10',
            'S' => '11',
            'T' => '12',
        ];
    }

    public static function generate(string $first_name, string $last_name, $birth_date, string $place, string $gender, CodiceFiscaleConfig $config = null): string
    {
        $config = $config ?: resolve(CodiceFiscaleConfig::class);

        $cf_gen = resolve(CodiceFiscaleGenerator::class);

        $cf_gen->nome = $first_name;
        $cf_gen->cognome = $last_name;

        $cf_gen->comune = $place;
        $cf_gen->sesso = $gender;

        if ($birth_date instanceof Carbon) {
            $date = $birth_date;
        } else {
            $date = Carbon::createFromFormat($config->getDateFormat(), $birth_date);
        }
        $cf_gen->data = $date;

        return $cf_gen->calcola();
    }

    /**
     * @returns bool
     */
    public function tryParse($cf): bool
    {
        try {
            $this->parse($cf);

            return true;
        } catch (\Exception $ex) {
            $this->error = $ex;

            return false;
        }
    }

    /**
     * @returns array
     *
     * @throws CodiceFiscaleValidationException
     */
    public function parse($cf): array
    {
        $cf = strtoupper($cf);
        $this->cf = $cf;
        $this->isValid = null;
        $this->gender = null;
        $this->birthPlace = null;
        $this->day = null;
        $this->month = null;
        $this->year = null;
        $this->error = null;

        foreach ($this->checks as $check) {
            (new $check())->check($cf);
        }

        $cfArray = str_split($cf);

        for ($i = 0; $i < count($this->tabReplacementOmocodia); $i++) {
            if (! is_numeric($cfArray[$this->tabReplacementOmocodia[$i]])) {
                $cfArray[$this->tabReplacementOmocodia[$i]] =
                    $this->tabDecodeOmocodia[$cfArray[$this->tabReplacementOmocodia[$i]]];
            }
        }

        $adaptedCF = implode($cfArray);

        $this->isValid = true;

        $this->gender = (substr($adaptedCF, 9, 2) > '40' ? $this->config->getFemaleLabel() : $this->config->getMaleLabel());

        $this->birthPlace = substr($adaptedCF, 11, 4);
        $this->year = substr($adaptedCF, 6, 2);
        $monthPart = substr($adaptedCF, 8, 1);
        $this->month = array_key_exists($monthPart, $this->tabDecodeMonths) ? $this->tabDecodeMonths[$monthPart] : null;

        $this->day = substr($adaptedCF, 9, 2);

        if ($this->gender === $this->config->getFemaleLabel()) {
            $this->day = $this->day - 40;
            if (strlen($this->day) === 1) {
                $this->day = '0'.$this->day;
            }
        }

        return $this->asArray();
    }

    public function isValid()
    {
        return $this->isValid;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function getBirthPlace()
    {
        return $this->birthPlace;
    }

    public function getBirthPlaceComplete()
    {
        if ($this->getBirthPlace() === null) {
            return;
        }

        if (! array_key_exists($this->getBirthPlace(), $this->cityDecoder->getList())) {
            throw new CodiceFiscaleValidationException(
                'Invalid codice fiscale',
                CodiceFiscaleValidationException::MISSING_CITY_CODE
            );
        }

        return ucwords(strtolower($this->cityDecoder->getList()[$this->getBirthPlace()]));
    }

    public function getBirthdate(): Carbon
    {
        try {
            return Carbon::parse($this->getYear().'-'.$this->getMonth().'-'.$this->getDay());
        } catch (\Exception $exception) {
            throw new CodiceFiscaleValidationException('Parsed date is not valid');
        }
    }

    public function getYear()
    {
        $current_year = Carbon::today()->year;
        if (2000 + $this->year < $current_year) {
            return '20'.$this->year;
        }

        return '19'.$this->year;
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function getDay()
    {
        return $this->day;
    }

    public function getCodiceFiscale()
    {
        return $this->cf;
    }

    /**
     * @throws CodiceFiscaleValidationException
     *
     * @return array
     */
    public function asArray(): array
    {
        return [
            'gender'               => $this->getGender(),
            'birth_place'          => $this->getBirthPlace(),
            'birth_place_complete' => $this->getBirthPlaceComplete(),
            'day'                  => $this->getDay(),
            'month'                => $this->getMonth(),
            'year'                 => $this->getYear(),
            'birthdate'            => $this->getBirthdate(),
        ];
    }
}
