<?php

namespace robertogallea\LaravelCodiceFiscale;


use Carbon\Carbon;

class CodiceFiscale
{
    public const NO_ERROR = 0;
    public const NO_CODE = 1;
    public const WRONG_SIZE = 2;
    public const BAD_CHARACTERS = 3;
    public const BAD_OMOCODIA_CHAR = 4;
    public const WRONG_CODE = 5;

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
    private $tabEvenChars = null;
    private $tabOddChars = null;
    private $tabControlCode = null;
    private $tabDecodeMonths = null;


    public function __construct()
    {
        $this->tabDecodeOmocodia = [
            "A" => "!",
            "B" => "!",
            "C" => "!",
            "D" => "!",
            "E" => "!",
            "F" => "!",
            "G" => "!",
            "H" => "!",
            "I" => "!",
            "J" => "!",
            "K" => "!",
            "L" => "0",
            "M" => "1",
            "N" => "2",
            "O" => "!",
            "P" => "3",
            "Q" => "4",
            "R" => "5",
            "S" => "6",
            "T" => "7",
            "U" => "8",
            "V" => "9",
            "W" => "!",
            "X" => "!",
            "Y" => "!",
            "Z" => "!",
        ];

        $this->tabReplacementOmocodia = [6, 7, 9, 10, 12, 13, 14];

        $this->tabEvenChars = [
            "0" => 0,
            "1" => 1,
            "2" => 2,
            "3" => 3,
            "4" => 4,
            "5" => 5,
            "6" => 6,
            "7" => 7,
            "8" => 8,
            "9" => 9,
            "A" => 0,
            "B" => 1,
            "C" => 2,
            "D" => 3,
            "E" => 4,
            "F" => 5,
            "G" => 6,
            "H" => 7,
            "I" => 8,
            "J" => 9,
            "K" => 10,
            "L" => 11,
            "M" => 12,
            "N" => 13,
            "O" => 14,
            "P" => 15,
            "Q" => 16,
            "R" => 17,
            "S" => 18,
            "T" => 19,
            "U" => 20,
            "V" => 21,
            "W" => 22,
            "X" => 23,
            "Y" => 24,
            "Z" => 25,
        ];

        $this->tabOddChars = [
            "0" => 1,
            "1" => 0,
            "2" => 5,
            "3" => 7,
            "4" => 9,
            "5" => 13,
            "6" => 15,
            "7" => 17,
            "8" => 19,
            "9" => 21,
            "A" => 1,
            "B" => 0,
            "C" => 5,
            "D" => 7,
            "E" => 9,
            "F" => 13,
            "G" => 15,
            "H" => 17,
            "I" => 19,
            "J" => 21,
            "K" => 2,
            "L" => 4,
            "M" => 18,
            "N" => 20,
            "O" => 11,
            "P" => 3,
            "Q" => 6,
            "R" => 8,
            "S" => 12,
            "T" => 14,
            "U" => 16,
            "V" => 10,
            "W" => 22,
            "X" => 25,
            "Y" => 24,
            "Z" => 23,
        ];
        
        $this->tabControlCode = [
            0 => "A",
            1 => "B",
            2 => "C",
            3 => "D",
            4 => "E",
            5 => "F",
            6 => "G",
            7 => "H",
            8 => "I",
            9 => "J",
            10 => "K",
            11 => "L",
            12 => "M",
            13 => "N",
            14 => "O",
            15 => "P",
            16 => "Q",
            17 => "R",
            18 => "S",
            19 => "T",
            20 => "U",
            21 => "V",
            22 => "W",
            23 => "X",
            24 => "Y",
            25 => "Z",
        ];

        $this->tabDecodeMonths = [
            "A" => "01",
            "B" => "02",
            "C" => "03",
            "D" => "04",
            "E" => "05",
            "H" => "06",
            "L" => "07",
            "M" => "08",
            "P" => "09",
            "R" => "10",
            "S" => "11",
            "T" => "12",
        ];
    }

    public static function generate(string $first_name, string $last_name, $birth_date, string $place, string $gender): string
    {
        $cf_gen = new CodiceFiscaleGenerator();

        $cf_gen->nome = $first_name;
        $cf_gen->cognome = $last_name;

        $cf_gen->comune = $place;
        $cf_gen->sesso = $gender;
        $cf_gen->formatoData('Y-m-d');
        if ($birth_date instanceof Carbon) {
            $date = $birth_date;
        } else {
            $date = Carbon::createFromFormat('Y-m-d', $birth_date);
        }
        $cf_gen->data = $date;
        return $cf_gen->calcola();

    }

    public function parse($cf)
    {
        $this->cf = $cf;
        $this->isValid = null;
        $this->gender = null;
        $this->birthPlace = null;
        $this->day = null;
        $this->month = null;
        $this->year = null;
        $this->error = null;

        if (($cf === null) || ($cf === "")) {
            $this->isValid = false;
            $this->error = self::NO_CODE;
            return false;
        }

        if (strlen($cf) !== 16) {
            $this->isValid = false;
            $this->error = self::WRONG_SIZE;
            return false;
        }

        $cf = strtoupper($cf);

        if (!preg_match("/^[A-Z0-9]+$/", $cf)) {
            $this->isValid = false;
            $this->error = self::BAD_CHARACTERS;
            return false;
        }

        $cfArray = str_split($cf);

        for ($i = 0; $i < count($this->tabReplacementOmocodia); $i++) {
            if ((!is_numeric($cfArray[$this->tabReplacementOmocodia[$i]])) &&
                ($this->tabDecodeOmocodia[$cfArray[$this->tabReplacementOmocodia[$i]]] === "!")) {
                $this->isValid = false;
                $this->error = self::BAD_OMOCODIA_CHAR;
                return false;
            }
        }

        $even = 0;
        $odd = $this->tabOddChars[$cfArray[14]];

        for ($i = 0; $i < 13; $i += 2) {
            $odd = $odd + $this->tabOddChars[$cfArray[$i]];
            $even = $even + $this->tabEvenChars[$cfArray[$i + 1]];
        }

        if (!($this->tabControlCode[($even + $odd) % 26] === $cfArray[15]) || (!$this->checkRegex($cf))) {
            $this->isValid = false;
            $this->error = self::WRONG_CODE;
            return false;
        } else {
            for ($i = 0; $i < count($this->tabReplacementOmocodia); $i++) {
                if (!is_numeric($cfArray[$this->tabReplacementOmocodia[$i]])) {
                    $cfArray[$this->tabReplacementOmocodia[$i]] = 
                        $this->tabDecodeOmocodia[$cfArray[$this->tabReplacementOmocodia[$i]]];
                }
            }

            $adaptedCF = implode($cfArray);

            $this->isValid = true;
            $this->error = self::NO_ERROR;

            $this->gender = (substr($adaptedCF, 9, 2) > "40" ? "F" : "M");
            $this->birthPlace = substr($adaptedCF, 11, 4);
            $this->year = substr($adaptedCF, 6, 2);
            $this->month = $this->tabDecodeMonths[substr($adaptedCF, 8, 1)];

            $this->day = substr($adaptedCF, 9, 2);
            if ($this->gender === "F") {
                $this->day = $this->day - 40;
                if (strlen($this->day) === 1)
                    $this->day = "0" . $this->day;
            }
        }
        return [
            'gender' => $this->getGender(),
            'birth_place' => $this->getBirthPlace(),
            'birth_place_complete' => $this->getBirthPlaceComplete(),
            'day'=> $this->getDay(),
            'month' => $this->getMonth(),
            'year' => $this->getYear(),
            'birthdate' => $this->getBirthdate(),
        ];
    }

    private function checkRegex($cf)
    {
        return preg_match('/^(?:(?:[B-DF-HJ-NP-TV-Z]|[AEIOU])[AEIOU][AEIOUX]|[B-DF-HJ-NP-TV-Z]{2}[A-Z]){2}' .
            '[\dLMNP-V]{2}(?:[A-EHLMPR-T](?:[04LQ][1-9MNP-V]|[1256LMRS][\dLMNP-V])|[DHPS][37PT][0L]|[ACELMRT][37PT' .
            '][01LM])(?:[A-MZ][1-9MNP-V][\dLMNP-V]{2}|[A-M][0L](?:[1-9MNP-V][\dLMNP-V]|[0L][1-9MNP-V]))[A-Z]$/i',
            $cf);
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
            return null;
        }
        return ucfirst(strtolower(ItalianCities::list[$this->getBirthPlace()]));
    }

    public function getYear()
    {
        $current_year = Carbon::today()->year;
        if (2000 + $this->year < $current_year) {
            return '20' . $this->year;
        }
        return '19' . $this->year;
    }

    public function getBirthdate(): Carbon
    {
        return Carbon::parse($this->getYear() . '-' . $this->getMonth() . '-' . $this->getDay());
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function getDay()
    {
        return $this->day;
    }
}